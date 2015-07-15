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
    $item_options,
    $optimal_size_quantity,
    $optimal_weight_quantity,
    $additional_handling;
    
    /**
     * Concrete implementation of AbstractOCPacker that complies with all constraints
     * @param object  $registry        Registry object from OpenCart
     * @param string  $shipper_prefix  Prefix used to retrieve shipping information from OpenCart's config file
     * @param integer $weight_code     Measurement unit code for the weight parameter (default is 'lb')
     * @param integer $length_code     Measurement unit code for the length parameter (default is 'in')
     */
    public function __construct($registry, $shipper_prefix = 'ups', $max_weight = 150, $max_length = 108, $max_size = 165, $weight_code = 'lb', $length_code = 'in') {
        parent::__construct($registry, $shipper_prefix, $max_weight, $max_length, $max_size, $weight_code, $length_code);
    }
    
    /**
     * @Override Bridge method to real worker
     */
    protected function getPackageWorker($item) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        // Determine basic product characteristics before processing
        $array = array_intersect_key($item, array('weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0));
        if (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'length', 'width', 'height', 'weight'");
        }
        extract($array);
        
        // Determine individual item weight and optimal quantity per package based on weight
        $this->item_weight = $this->weight->convert($weight, $item['weight_class_id'], $this->weight_class_id);
        $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1)));
        $this->item_weight = max(0.1, $this->item_weight / $quantity);
        $this->optimal_weight_quantity = max(1, $this->preferred_weight / $this->item_weight);
        
        // Item dimensions and optimal quantity per package based on size
        $this->item_length = $this->length->convert($length, $item['length_class_id'], $this->length_class_id);
        $this->item_width = $this->length->convert($width, $item['length_class_id'], $this->length_class_id);
        $this->item_height = $this->length->convert($height, $item['length_class_id'], $this->length_class_id);
        $this->optimal_size_quantity = max(1, ((($this->preferred_size - $this->item_length) / 2) - $this->item_height) / $this->item_width);
        
        // Default item options, e.g. packaging type, signature required, etc.
        // ['options'] is not part of the default OpenCart product model, but could be as part of a module
        $this->item_options = (empty($item['options']) || !is_array($item['options']) ? array() : $item['options']);
        if (!array_key_exists('type', $this->item_options) && $this->packaging_type !== null) {
            $this->item_options['type'] = $this->packaging_type;
        }
        // Check if item has additional handling flag (again, not part of default model)
        // $this->item_options['additional_handling'] = true;
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
            $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1)));
            $width_modifier = max(1, ceil(sqrt($quantity))); // will give approximate size (either exact or over-estimated)
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
            $total_size = $this->item_length + (2 * ($width + $height));
            if ($weight > $this->max_weight || $total_size > $this->max_size 
                || ($total_size > $this->preferred_size && $quantity > $this->optimal_size_quantity) 
                || ($weight > $this->preferred_weight && $quantity > $this->optimal_weight_quantity))
            {
                if ($quantity === 1) {
                    throw new \InvalidArgumentException("Item exceeds maximum package weight or size requirements");
                }
                // recursively split items into separate packages
                $tmp = array($item, $item); // is this a shallow copy? will it cause issues?
                $tmp[0]['quantity'] = ceil($quantity / 2.0);
                $tmp[1]['quantity'] = $quantity - $tmp[0]['quantity'];
                $tmp[0]['total'] = ($item['price'] * $tmp[0]['quantity']);
                $tmp[1]['total'] = ($item['price'] * $tmp[1]['quantity']);
                $packages = array_merge($packages, $this->recursivePackageWorker($tmp));
            } else {
                $options = $this->item_options;
                if ($this->requires_insurance || array_key_exists('insured_amount', $options)) {
                    $options['insured_amount'] = $item['total'];
                }
                $package = new \Awsp\Ship\Package($weight, array($this->item_length, $width, $height), $options);
                $packages[] = $package;
            }
        }
        return $packages;
    }
}
