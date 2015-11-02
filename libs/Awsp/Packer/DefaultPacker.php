<?php
/**
 * Default packer implementation packages each item individually within the constraints provided.
 * Each item is represented by an array.
 *
 * @package Awsp Packer Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer;

class DefaultPacker extends AbstractPacker
{
    /**
     * @Override Packs each item individually
     * @param array $item 'weight', 'length', 'width', and 'height' are required
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
        // Determine individual item weight
        $quantity = (array_key_exists('quantity', $item) ? filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1))) : 1);
        if ($this->is_weight_combined && $quantity > 1) {
            $weight = max(0.1, ($weight / $quantity));
        }
        // Create and validate the package
        $options = $this->getPackageOptions($item);
        $package = new \Awsp\Ship\Package($weight, array($length, $width, $height), $options);
        if (!$this->checkConstraints($package, $error)) { // don't care about optional constraints
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        return array_fill(0, $quantity, $package);
    }
}
