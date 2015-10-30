<?php
/**
 * Default packer implementation packages each item individually within the constraints provided.
 * Each item is represented by an array.
 *
 * @package Awsp\Packer\Vendor\Cubecart
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\CubeCart;

class DefaultPacker extends \Awsp\Packer\AbstractPacker
{
    /** Weight of packaging (if any) based on store settings */
    private $packaging_weight = 0.0;

    /** Default package dimensions based on store settings */
    private $default_dimensions = array('product_length' => 8, 'product_width' => 8, 'product_height' => 8);

    /**
     * Sets the amount of weight to add to each package to account for packaging material
     * @param float $value Must also expect possible empty string in place of zero from CubeCart
     */
    public function setPackagingWeight($value) {
        $this->packaging_weight = (empty($value) ? 0 : max(0.0, $this->getValidatedFloat($value)));
    }

    /**
     * Sets default package dimensions for cases in which a product's dimensions are not specified
     * @param array Array containing exactly 3 float or integer values
     */
    public function setDefaultDimensions(array $dimensions) {
        $values = filter_var(array_values($dimensions), FILTER_VALIDATE_FLOAT, FILTER_REQUIRE_ARRAY);
        if (!is_array($values) || count($values) !== 3) {
            throw new \InvalidArgumentException("Expected exactly 3 float or integer values contained in an array; received: " . implode(',', $dimensions));
        }
        rsort($values);
        $this->default_dimensions = array_combine(array_keys($this->default_dimensions), $values);
    }

    /**
     * @Override Packs each item individually
     * @param array $item An array containing 'product_weight', 'product_length', 'product_height', 'product_width', and possibly 'quantity'
     */
    protected function getPackageWorker($item, array &$packages) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        // Extract required values from $item parameter
        $array = array_intersect_key($item, array('product_weight' => 0, 'product_length' => 0, 'product_height' => 0, 'product_width' => 0));
        // Set any missing or invalid product dimensions to the store defaults
        $n = 3; // number of set dimensions
        foreach ($this->default_dimensions as $k => $v) {
            if (!array_key_exists($k, $array) || !filter_var($array[$k], FILTER_VALIDATE_FLOAT) || $array[$k] <= 0) {
                $array[$k] = (float) $v;
                $n--;
            }
        }
        // Product dimensions must either not be set at all, or all must be set
        if ($n < 3 && $n > 0) {
            throw new \InvalidArgumentException("Product dimensions must all be set or all left blank: $n dimension(s) were set");
        } elseif (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'product_length', 'product_height', 'product_width', 'product_weight', and usually 'quantity'");
        }
        extract($array);
        $quantity = $this->getQuantityFromItem($item);
        // CubeCart product weight is already per-item, but should be adjusted for store settings
        $product_weight += $this->packaging_weight;
        
        // Build and validate package with single item
        $options = $this->getPackageOptions($item);
        $package = new \Awsp\Ship\Package($product_weight, array($product_length, $product_height, $product_width), $options);
        if (!$this->checkConstraints($package, $error)) { // don't care about optional constraints
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        return array_fill(0, $quantity, $package);
    }

    /**
     * Override
     * CubeCart products do not have any shipping-related options at this time
     */
    protected function getPackageOptions($item) {
        return array();
    }
}
