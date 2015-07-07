<?php
/**
 * Default packer implementation packages each item individually within the constraints provided.
 * Each item is represented by an array.
 *
 * @package Awsp Shipping Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Ship;

class DefaultPacker extends AbstractPacker
{
    /** True if items passed to #getPackageWorker use a combined weight (item weight * quantity) */
    private $is_weight_combined;

    /**
     * For complete details, see AbstractPacker#__construct
     * @param $is_weight_combined True if items passed to #getPackageWorker use a combined weight (item weight * quantity)
     */
    public function __construct($max_weight = 150, $max_length = 108, $max_size = 165, $is_weight_combined = false) {
        parent::__construct($max_weight, $max_length, $max_size);
        $this->is_weight_combined = filter_var($is_weight_combined, FILTER_VALIDATE_BOOLEAN);
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
        $quantity = (array_key_exists('quantity', $item) ? filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1))) : 1);
        if ($this->is_weight_combined && $quantity > 1) {
            $weight = max(0.1, ($weight / $quantity));
        }
        $options = (empty($item['options']) || !is_array($item['options']) ? array() : $item['options']);
        $package = new Package($weight, array($length, $width, $height), $options);
        if ($package->get('weight') > $this->max_weight || $package->get('length') > $this->max_length || $package->get('size') > $this->max_size) {
            throw new \InvalidArgumentException("Item exceeds maximum package weight or size requirements");
        }
        return array_fill(0, $quantity, $package);
    }
}
