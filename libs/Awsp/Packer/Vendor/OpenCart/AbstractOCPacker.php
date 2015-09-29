<?php
/**
 * Base packing class for OpenCart implementations uses shipper configuration settings
 * to convert all measurements to the appropriate unit and stores related objects such
 * as the OC weight and length objects for use during packing.
 *
 * @package Awsp\Packer\Vendor\OpenCart Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer\Vendor\OpenCart;

abstract class AbstractOCPacker extends \Awsp\Packer\AbstractPacker
{
    /** Shipper's default packaging type, may be null */
    protected $packaging_type;
    
    /** Whether the insurance option was selected in the shipper's settings */
    protected $requires_insurance;
    
    /** OpenCart weight object (for converting) */
    protected $weight;
    
    /** Shipper's weight class id (for converting) */
    protected $weight_class_id;
    
    /** Weight code for the shipper's API, not necessarily the same as used in OpenCart */
    protected $weight_code;
    
    /** Preferred maximum weight (i.e. to avoid extra charges) */
    protected $preferred_weight;
    
    /** OpenCart length object (for converting) */
    protected $length;
    
    /** Shipper's length class id (for converting) */
    protected $length_class_id;
    
    /** Length code for the shipper's API, not necessarily the same as used in OpenCart */
    protected $length_code;
    
    /** Preferred maximum total size (i.e. to avoid extra charges) */
    protected $preferred_size;
    
    /**
     * Abstract OpenCart packer requires maximum allowed weight, length, and total size.
     * All units are converted based on shipper module settings.
     * @param object $registry        Registry object from OpenCart
     * @param string $shipper_prefix  Prefix used to retrieve shipping information from OpenCart's config file
     * @param string $weight_code     Measurement unit code for all weight parameters (default is 'lb')
     * @param string $length_code     Measurement unit code for all length parameters (default is 'in')
     */
    public function __construct($registry, $shipper_prefix = 'ups', $max_weight = 150, $max_length = 108, $max_size = 165, $weight_code = 'lb', $length_code = 'in') {
        parent::__construct($max_weight, $max_length, $max_size);
        $config = $registry->get('config');
        $this->weight = $registry->get('weight');
        $this->length = $registry->get('length');
        $this->packaging_type = $this->getPackagingType($config, $shipper_prefix);
        $this->requires_insurance = ($config->has($shipper_prefix . '_insurance') ? $config->get($shipper_prefix . '_insurance') : null);
        $this->weight_class_id = $config->get($shipper_prefix . '_weight_class_id');
        $this->weight_code = strtoupper($this->weight->getUnit($this->weight_class_id));
        if ($this->weight_code == 'KG') {
            $this->weight_code = 'KGS';
        } elseif ($this->weight_code == 'LB') {
            $this->weight_code = 'LBS';
        }
        $this->length_class_id = $config->get($shipper_prefix . '_length_class_id');
        $this->length_code = strtoupper($this->length->getUnit($this->length_class_id));
        
        // Convert weight and length values so they can stay the same regardless of config settings
        $this->max_weight = $this->weight->convert($this->max_weight, $this->getWeightCodeId($weight_code), $this->weight_class_id);
        $this->preferred_weight = $this->max_weight; // default until set otherwise
        
        $length_code_id = $this->getLengthCodeId($length_code);
        $this->max_length = $this->length->convert($this->max_length, $length_code_id, $this->length_class_id);
        $this->max_size = $this->length->convert($this->max_size, $length_code_id, $this->length_class_id);
        $this->preferred_size = $this->max_size; // default until set otherwise
    }
    
    /**
     * Set the preferred package size
     * @param integer size        Usually the max size before a package is considered 'large'
     * @param string  length_code A valid weight code, e.g. 'in' or 'cm' (case-insensitive)
     * @return Returns itself for convenience
     */
    public function setPreferredSize($size = 130, $length_code = 'in') {
        if (!is_int($size)) {
            throw new \InvalidArgumentException("Expected integer for 'size'; received " + getType($size));
        }
        $this->preferred_size = $this->length->convert($size, $this->getLengthCodeId($length_code), $this->length_class_id);
        return $this;
    }
    
    /**
     * Set the preferred package weight
     * @param integer weight      Usually the max weight before a package is considered 'heavy'
     * @param string  weight_code A valid weight code, e.g. 'lb' or 'kg' (case-insensitive)
     * @return Returns itself for convenience
     */
    public function setPreferredWeight($weight = 70, $weight_code = 'lb') {
        if (!is_int($weight)) {
            throw new \InvalidArgumentException("Expected integer for 'weight'; received " + getType($weight));
        }
        $this->preferred_weight = $this->weight->convert($weight, $this->getWeightCodeId($weight_code), $this->weight_class_id);
        return $this;
    }
    
    /**
     * Returns the packaging type based on the shipper, since OpenCart does not use a standard naming scheme -.-
     */
    private function getPackagingType($config, $shipper) {
        if ($config->has($shipper . '_packaging')) { // UPS
            return $config->get($shipper . '_packaging');
        } elseif ($config->has($shipper . '_packaging_type')) { // Fedex
            return $config->get($shipper . '_packaging_type');
        } // none of the other shippers appear to require packaging type
        return null;
    }
    
    /**
     * Returns the OpenCart id of the given length code or throws an exception
     * @param string  length_code A valid length code, e.g. 'in' or 'cm' (case-insensitive)
     * @throws UnexpectedValueException if unable to determine the length code id
     */
    private function getLengthCodeId($length_code) {
        if (!ctype_alpha($length_code)) {
            throw new \InvalidArgumentException("Length code may only contain alphabetic characters; received '$length_code'");
        }
        $length_code = strtolower($length_code);
        // Lacking a getter, resort to reflection to access private 'lengths' array
        $length_class = new \ReflectionClass(get_class($this->length));
        $property = $length_class->getProperty("lengths");
        $property->setAccessible(true);
        foreach ($property->getValue($this->length) as $length) {
            if (strtolower($length['unit']) === $length_code) {
                return $length['length_class_id'];
            }
        }
        throw new \UnexpectedValueException("Unable to determine length code id for length code '$length_code'");
    }
    
    /**
     * Returns the OpenCart id of the given weight code or throws an exception
     * @param string  weight_code A valid weight code, e.g. 'lb' or 'kg' (case-insensitive)
     * @throws UnexpectedValueException if unable to determine the weight code id
     */
    private function getWeightCodeId($weight_code) {
        if (!ctype_alpha($weight_code)) {
            throw new \InvalidArgumentException("Weight code may only contain alphabetic characters; received '$weight_code'");
        }
        $weight_code = strtolower($weight_code);
        // Lacking a getter, resort to reflection to access private 'weights' array
        $weight_class = new \ReflectionClass(get_class($this->weight));
        $property = $weight_class->getProperty("weights");
        $property->setAccessible(true);
        foreach ($property->getValue($this->weight) as $weight) {
            if (strtolower($weight['unit']) === $weight_code) {
                return $weight['weight_class_id'];
            }
        }
        throw new \UnexpectedValueException("Unable to determine weight code id for weight code '$weight_code'");
    }
}
