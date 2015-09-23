<?php
/**
 * Default OpenCart packer implementation packages each item individually.
 *
 * @package Awsp\Packer\Vendor\OpenCart Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\OpenCart;

class DefaultPacker extends AbstractOCPacker
{
    /**
     * Concrete implementation of AbstractOCPacker packages each item individually
     * provided the item does not exceed max allowed weight, length, or total size
     * @param object  $registry        Registry object from OpenCart
     * @param string  $shipper_prefix  Prefix used to retrieve shipping information from OpenCart's config file
     * @param integer $weight_code     Measurement unit code for the weight parameter (default is 'lb')
     * @param integer $length_code     Measurement unit code for the length parameter (default is 'in')
     */
    public function __construct($registry, $shipper_prefix = 'ups', $max_weight = 150, $max_length = 108, $max_size = 165, $weight_code = 'lb', $length_code = 'in') {
        parent::__construct($registry, $shipper_prefix, $max_weight, $max_length, $max_size, $weight_code, $length_code);
    }
    
    /**
     * @Override Packs each item individually
     * @param array $item An array containing 'weight', 'length', 'width', 'height', and possibly 'quantity'
     */
    protected function getPackageWorker($item) {
        if (!is_array($item)) {
            throw new \InvalidArgumentException("Expected item to be an array; received " . getType($item));
        }
        $array = array_intersect_key($item, array('weight' => 0, 'length' => 0, 'width' => 0, 'height' => 0));
        if (count($array) < 4) {
            throw new \InvalidArgumentException("Item must contain the following fields: 'length', 'width', 'height', 'weight', and usually 'quantity'");
        }
        extract($array);
        
        // Determine individual item weight
        $quantity = (array_key_exists('quantity', $item) ? filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1))) : 1);
        $weight = $this->weight->convert($weight, $item['weight_class_id'], $this->weight_class_id);
        $weight = max(0.1, $weight / $quantity);
        
        // Convert item dimensions based on current measurement unit settings (will be sorted in Package constructor)
        $length = $this->length->convert($length, $item['length_class_id'], $this->length_class_id);
        $width = $this->length->convert($width, $item['length_class_id'], $this->length_class_id);
        $height = $this->length->convert($height, $item['length_class_id'], $this->length_class_id);
        
        $options = (empty($item['options']) || !is_array($item['options']) ? array() : $item['options']);
        $package = new \Awsp\Ship\Package($weight, array($length, $width, $height), $options);
        if ($package->get('weight') > $this->max_weight || $package->get('length') > $this->max_length || $package->get('size') > $this->max_size) {
            throw new \InvalidArgumentException("Item exceeds maximum package weight or size requirements");
        }
        return array_fill(0, $quantity, $package);
    }
}
