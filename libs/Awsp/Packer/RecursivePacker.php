<?php
/**
 * This implementation first attempts to merge items into previous packages using
 * available IMergeStrategies, if any; remaining items are then packed recursively
 * in either a square or vertical stack, depending on which is more effecient.
 *
 * Each item is represented by an array.
 *
 * @package Awsp Packer Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/23/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer;

class RecursivePacker extends AbstractPacker
{
    /** Item packaged individually, used as a reference during merging and recursive packing */
    protected $single_item;

    /**
     * Override
     */
    protected function getPackageOptions($item) {
        $options = parent::getPackageOptions($item);
        // Adjust insured amount
        $insured_amount = (empty($options['insured_amount']) ? 0 : $options['insured_amount']);
        if (!empty($options['insured_amount'])) {
            $options['insured_amount'] *= $this->getQuantityFromItem($item);
        }
        return $options;
    }

    /**
     * @Override
     * @param array $item An array containing 'weight', 'length', 'width', 'height', and possibly 'quantity'
     */
    protected function getPackageWorker($item, array &$packages) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        // Extract required values from $item parameter
        $array = array_intersect_key($item, array('weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0));
        if (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'length', 'width', 'height', 'weight', and usually 'quantity'");
        }
        extract($array);
        $quantity = $this->getQuantityFromItem($item);
        
        // Determine individual item weight
        if ($this->is_weight_combined && $quantity > 1) {
            $weight = max(0.1, ($weight / $quantity));
        }
        
        // Convert item dimensions and sort
        $lwh = $this->getSortedDimensions($this->getMeasurementValue($length), $this->getMeasurementValue($width), $this->getMeasurementValue($height));
        
        // Determine package options for single item and adjust insurance amount
        $options = $this->getPackageOptions($item);
        if ($quantity > 1 && array_key_exists('insured_amount', $options)) {
            $options['insured_amount'] /= $quantity;
        }
        
        // Ensure item is at least able to be packed individually
        $this->single_item = new \Awsp\Ship\Package($weight, $lwh, $options);
        $this->setAdditionalHandlingConstraint(false); // don't check additional handling on single items
        if (!$this->checkConstraints($this->single_item, $error)) {
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        if (!empty($this->merge_strategies)) {
            $quantity = $this->merge($packages, $this->single_item, $quantity);
        }
        
        // Pack remaining quantity recursively for a fairly accurate estimate
        if ($quantity > 1) {
            // Update item with converted unit values, remaining quantity, etc.
            $item = array_merge($item, $lwh, array('weight'=>$weight, 'quantity'=>$quantity, 'options'=>$options));
            
            // If item packaged singly does not require additional handling, add the constraint to avoid the extra fee
            // Note that it may be possible for the fee to be worth it if, for example, many items can be packaged together
            if ($this->handling_constraint != null) {
                $flag = $this->handling_constraint->check($this->single_item);
                $this->setAdditionalHandlingConstraint($this->handling_constraint->check($this->single_item));
            }
            return $this->recursivePackageWorker(array($item));
        }
        // Remaining quantity is either 0 or 1; return array filled with that many packages
        return ($quantity > 0 ? array($this->single_item) : array());
    }

    /**
     * Recursively packs items into packages, distributing quantity as evenly as possible
     * @param items Array of items, where each entry is a complete item
     */
    protected function recursivePackageWorker(array $items) {
        $packages = array();
        // Break items up into suitable packages based on max weight and max size
        foreach ($items AS $item) {
            // Current item characteristics
            $quantity = $this->getQuantityFromItem($item);
            
            // 'Square' stacking (i.e. stack on both width and height)
            $width_modifier = (int) max(1, ceil(sqrt($quantity))); // will give approximate size (either exact or over-estimated)
            $height_modifier = $width_modifier;
            
            // Adjust modifiers to prevent gross overestimation
            if (($height_modifier * $width_modifier) > $quantity) {
                --$height_modifier;
                if (($height_modifier * $width_modifier) < $quantity) {
                    ++$width_modifier;
                }
            }
            $weight = $this->single_item->get('weight') * $quantity;
            $width = $width_modifier * $this->single_item->get('width');
            $height = $height_modifier * $this->single_item->get('height');
            
            // Re-sort dimensions, in case width or height now exceeds length
            $lwh = $this->getSortedDimensions($this->single_item->get('length'), $width, $height);
            extract($lwh);
            $total_size = $length + (2 * ($width + $height));
            
            // Vertical stacking comparison (height should be the smallest dimension)
            $lwh = $this->getSortedDimensions($this->single_item->get('length'), $this->single_item->get('width'), ($this->single_item->get('height') * $quantity));
            $vertical_size = $lwh['length'] + (2 * ($lwh['width'] + $lwh['height']));
            if ($vertical_size < $total_size) {
                extract($lwh); // vertical stacking is more efficient, overwrite existing dimensions
                $total_size = $vertical_size;
            }
            
            // Must meet all required constraints to pack singly, and optional constraints to pack with other items
            $package = new \Awsp\Ship\Package($weight, array($length, $width, $height), $this->getPackageOptions($item));
            if ($this->checkConstraints($package, $error) && ($quantity === 1 || $this->checkOptionalConstraints($package, $error))) {
                $packages[] = $package;
            } elseif ($quantity === 1) { // couldn't be packed even as a single item
                throw new \InvalidArgumentException("Invalid package: $error");
            } else { // recursively split items into separate packages
                $packages = array_merge($packages, $this->recursivePackageWorker($this->splitItem($item)));
            }
        }
        return $packages;
    }
}
