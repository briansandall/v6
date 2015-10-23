<?php
/**
 * This implementation first attempts to merge items into previous packages using
 * available IMergeStrategies, if any; remaining items are then packed recursively
 * in either a square or vertical stack, depending on which is more effecient.
 *
 * CubeCart item array entries are typically prefixed with 'product_', e.g. 'product_weight'.
 *
 * @package Awsp\Packer\Vendor\Cubecart
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/23/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\Cubecart;

class RecursivePacker extends \Awsp\Packer\RecursivePacker
{
    /** Weight of packaging (if any) based on store settings */
    private $packaging_weight = 0.0;

    /**
     * Sets the amount of weight to add to each package to account for packaging material
     * @param float $value Must also expect possible empty string in place of zero from CubeCart
     */
    public function setPackagingWeight($value) {
        $this->packaging_weight = (empty($value) ? 0 : max(0.0, $this->getValidatedFloat($value)));
    }

    /**
     * @Override
     * @param array $item An array containing 'product_weight', 'product_length', 'product_height', 'product_width', and possibly 'quantity'
     */
    protected function getPackageWorker($item, array &$packages) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        // Extract required values from $item parameter
        $array = array_intersect_key($item, array('product_weight' => 0, 'product_length' => 0, 'product_height' => 0, 'product_width' => 0));
        if (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'product_length', 'product_height', 'product_width', 'product_weight', and usually 'quantity'");
        }
        extract($array);
        $quantity = $this->getQuantityFromItem($item);
        // CubeCart product weight is already per-item; packaging weight added on a per-package basis later
        
        // Convert item dimensions and sort
        $lwh = $this->getSortedDimensions($this->getMeasurementValue($product_length), $this->getMeasurementValue($product_width), $this->getMeasurementValue($product_height));
        
        // Determine package options for single item and adjust insurance amount
        $options = $this->getPackageOptions($item);
        if ($quantity > 1 && array_key_exists('insured_amount', $options)) {
            $options['insured_amount'] /= $quantity;
        }
        
        // Ensure item is at least able to be packed individually
        $this->single_item = new \Awsp\Ship\Package($product_weight, $lwh, $options);
        $this->setAdditionalHandlingConstraint(false); // don't check additional handling on single items
        if (!$this->checkConstraints($this->single_item, $error)) {
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        if (!empty($this->merge_strategies)) {
            $quantity = $this->merge($packages, $this->single_item, $quantity);
        }
        // If more than one remain, pack recursively
        if ($quantity > 1) {
            // Update item with converted unit values, remaining quantity, etc.
            $item = array_merge($item, $lwh, array('weight'=>$product_weight, 'quantity'=>$quantity, 'options'=>$options));
            
            // If item packaged singly does not require additional handling, add the constraint to avoid the extra fee
            // Note that it may be possible for the fee to be worth it if, for example, many items can be packaged together
            if ($this->handling_constraint != null) {
                $flag = $this->handling_constraint->check($this->single_item);
                $this->setAdditionalHandlingConstraint($this->handling_constraint->check($this->single_item));
            }
            $new_packages = $this->recursivePackageWorker(array($item));
            // Adjust package weight for packaging material
            if ($this->packaging_weight > 0) {
                foreach ($new_packages as $index => $package) {
                    $new_packages[$index] = $this->getAdjustedPackage($package);
                }
            }
            return $new_packages;
        }
        // Adjust package weight for packaging material
        if ($quantity > 0 && $this->packaging_weight > 0) {
            $this->single_item = $this->getAdjustedPackage($this->single_item);
        }
        // Remaining quantity is either 0 or 1; return array filled with that many packages
        return ($quantity > 0 ? array($this->single_item) : array());
    }

    /**
     * Override
     * CubeCart products do not have any shipping-related options at this time
     */
    protected function getPackageOptions($item) {
        return array();
    }

    private function getAdjustedPackage(\Awsp\Ship\Package $package) {
        $weight = $package->get('weight') + $this->packaging_weight;
        $dimensions = array($package->get('length'), $package->get('width'), $package->get('height'));
        return new \Awsp\Ship\Package($weight, $dimensions, $package->get('options'));
    }
}
