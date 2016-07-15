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
 * @copyright (c) 2016 Brian Sandall
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\OpenCart;

class PackByProduct extends AbstractOCPacker
{
    /** Item packaged individually, used as a reference during merging and recursive packing */
    private $single_item;
    
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
        $quantity = $this->getQuantityFromItem($item);
        
        // Determine individual item weight
        $weight = $this->weight->convert($weight, $item['weight_class_id'], $this->weight_class_id);
        if ($this->is_weight_combined && $quantity > 1) {
            $weight = max(0.1, ($weight / $quantity));
        }
        
        // Convert item dimensions and sort
        $length = $this->length->convert($length, $item['length_class_id'], $this->length_class_id);
        $width = $this->length->convert($width, $item['length_class_id'], $this->length_class_id);
        $height = $this->length->convert($height, $item['length_class_id'], $this->length_class_id);
        $lwh = $this->getSortedDimensions($length, $width, $height);
        extract($lwh); // overwrite previous variables with sorted values
        
        // Determine package options for single item and adjust insurance amount
        $options = $this->getPackageOptions($item);
        if ($quantity > 1 && array_key_exists('insured_amount', $options)) {
            $options['insured_amount'] /= $quantity;
        }
        
        // Ensure item is at least able to be packed individually
        $this->single_item = new \Awsp\Ship\Package($weight, $lwh, $options);
        if (!$this->checkConstraints($this->single_item, $error)) {
            throw new \InvalidArgumentException("Invalid package: $error");
        }
        // Toggle optional constraints based on item packed singly
        $this->updateOptionalConstraints($this->single_item);
        
        // Attempt to merge item(s) with existing package(s)
        if (!empty($this->merge_strategies)) {
            $quantity = $this->merge($packages, $this->single_item, $quantity);
        }
        // Pack remaining quantity recursively for a fairly accurate estimate
        if ($quantity > 1) {
            // Update item with converted unit values, remaining quantity, etc.
            $item = array_merge($item, $lwh, array('weight'=>$weight, 'quantity'=>$quantity, 'options'=>$options));
            // Reset optional constraint status to that of single item after attempted merging
            $this->updateOptionalConstraints($this->single_item);
            return $this->recursivePackageWorker(array($item));
        }
        // Remaining quantity is either 0 or 1; return array filled with that many packages
        return ($quantity > 0 ? array($this->single_item) : array());
    }
    
    /**
     * Recursively packs items into packages, fitting as many in each package as possible
     * @param $items Array of items, where each entry is a complete item
     */
    protected function recursivePackageWorker(array $items, array $packages = array()) {
        // Break items up into suitable packages based on max weight and max size
        foreach ($items AS $item) {
            // Current item characteristics
            $quantity = $this->getQuantityFromItem($item);
            
            // 'Square' stacking
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
                extract($lwh); // overwrite existing dimensions
                $total_size = $vertical_size;
            }
            $options = $this->getPackageOptions($item);
            $package = new \Awsp\Ship\Package($weight, array($length, $width, $height), $options);
            if ($this->checkConstraints($package, $error) && ($quantity === 1 || $this->checkOptionalConstraints($package, $error))) {
                // try to merge new package into previous packages; otherwise add it
                if (empty($packages) || $this->merge($packages, $package, 1) > 0) {
                    $packages[] = $package;
                }
            } elseif ($quantity === 1) { // couldn't be packed even as a single item
                throw new \InvalidArgumentException("Invalid package: $error");
            } else { // recursively split items into separate packages
                $packages = $this->recursivePackageWorker($this->splitItem($item), $packages);
            }
        }
        return $packages;
    }

    /**
     * @Override
     */
    protected function splitItem($item, $quantity) {
        $tmp = parent::splitItem($item, $quantity);
        $tmp[0]['total'] = ($item['price'] * $tmp[0]['quantity']);
        $tmp[1]['total'] = ($item['price'] * $tmp[1]['quantity']);
        return $tmp;
    }
}
