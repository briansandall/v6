<?php
/**
 * Packing implementation that packages as many of the same item as possible in
 * the same package while staying under the oversize package limit. This gives
 * lower costs than the DefaultPacker implementation, but is not perfect as it
 * does not attempt to pack different products into the same package, nor does it
 * attempt to evaluate when a single oversize package may be more economical than
 * many smaller packages.
 *
 * @package Awsp\Packer\Vendor\OpenCart Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\OpenCart;

class PackByProduct extends AbstractOCPacker
{
    /** Temporary variables set for each item as it is processed */
    private
    $item_weight,
    $item_length,
    $item_width,
    $item_height,
    $optimal_size_quantity,
    $optimal_weight_quantity;
    
    /**
     * @Override Bridge method to real worker
     * @param array $item An OpenCart product, e.g. an element from OpenCart's shopping cart's array of products
     */
    protected function getPackageWorker($item, array &$packages) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        // Extract required values from $item parameter
        $array = array_intersect_key($item, array('weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0));
        if (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'length', 'width', 'height', 'weight'");
        }
        extract($array);
        
        // Make sure length is the longest dimension before attempting to build packages
        $lwh = array($length, $width, $height);
        rsort($lwh, SORT_NUMERIC);
        
        // Determine individual item weight and optimal quantity per package based on weight
        $this->item_weight = $this->weight->convert($weight, $item['weight_class_id'], $this->weight_class_id);
        $quantity = $this->getQuantityFromItem($item);
        $this->item_weight = max(0.1, $this->item_weight / $quantity);
        $this->optimal_weight_quantity = (int) max(1, floor($this->preferred_weight / $this->item_weight));
        
        // Item dimensions and optimal quantity per package based on size
        $this->item_length = $this->length->convert($lwh[0], $item['length_class_id'], $this->length_class_id);
        $this->item_width = $this->length->convert($lwh[1], $item['length_class_id'], $this->length_class_id);
        $this->item_height = $this->length->convert($lwh[2], $item['length_class_id'], $this->length_class_id);
        // Size = l + 2n(w + h), assuming 'square' proportions, so optimal n = (s - l) / 2(w + h), fitting n-squared items
        $this->optimal_size_quantity = (int) max(1, floor(($this->preferred_size - $this->item_length) / (2 * ($this->item_width + $this->item_height))));
        return $this->recursivePackageWorker(array($item));
    }
    
    /**
     * Recursively packs items into packages, fitting as many in each package as possible
     * @param $items Array of items, where each entry is a complete item
     */
    private function recursivePackageWorker(array $items) {
        $packages = array();
        // Break items up into suitable packages based on max weight and max size
        foreach ($items AS $item) {
            // Current item characteristics
            $quantity = $this->getQuantityFromItem($item);
            $width_modifier = (int) max(1, ceil(sqrt($quantity))); // will give approximate size (either exact or over-estimated)
            $height_modifier = $width_modifier;
            // Adjust modifiers to prevent gross overestimation
            if (($height_modifier * $width_modifier) > $quantity) {
                --$height_modifier;
                if (($height_modifier * $width_modifier) < $quantity) {
                    ++$width_modifier;
                }
            }
            $weight = $this->item_weight * $quantity;
            $width = $width_modifier * $this->item_width;
            $height = $height_modifier * $this->item_height;
            // Re-sort dimensions, in case width or height now exceeds length
            $lwh = array($item['length'], $width, $height);
            rsort($lwh);
            $lwh = array_combine(array('length','width','height'), $lwh); // re-add array keys
            extract($lwh);
            $total_size = $length + (2 * ($width + $height));
            $options = $this->getPackageOptions($item);
            $package = new \Awsp\Ship\Package($weight, array($length, $width, $height), $options);
            if ($this->checkConstraints($package, $error) && ($quantity === 1 || $this->checkOptionalConstraints($package, $error))) {
                $packages[] = $package;
            } elseif ($quantity === 1) {
                throw new \InvalidArgumentException("Invalid package: $error");
            } else { // recursively split items into separate packages
                $tmp = array($item, $item); // is this a shallow copy? will it cause issues?
                $tmp[0]['quantity'] = ceil($quantity / 2.0);
                $tmp[1]['quantity'] = $quantity - $tmp[0]['quantity'];
                $tmp[0]['total'] = ($item['price'] * $tmp[0]['quantity']);
                $tmp[1]['total'] = ($item['price'] * $tmp[1]['quantity']);
                $packages = array_merge($packages, $this->recursivePackageWorker($tmp));
            }
        }
        return $packages;
    }
}
