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
    
    /** Weight code for the shipper's API, not necessarily the same as used in OpenCart */
    protected $weight_code;
    
    /** Shipper's weight class id (for converting) */
    protected $weight_class_id;
    
    /** Default weight class for parameters (for converting values to the shipper's weight class) */
    protected $default_weight_class_id;
    
    /** OpenCart length object (for converting) */
    protected $length;
    
    /** Length code for the shipper's API, not necessarily the same as used in OpenCart */
    protected $length_code;
    
    /** Shipper's length class id (for converting) */
    protected $length_class_id;
    
    /** Default length class for parameters (for converting values to the shipper's length class) */
    protected $default_length_class_id;
    
    /**
     * Abstract OpenCart packer requires maximum allowed weight, length, and total size.
     * All units are converted based on shipper module settings.
     * @param object $registry        Registry object from OpenCart
     * @param string $shipper_prefix  Prefix used to retrieve shipping information from OpenCart's config file
     * @param string $weight_code     Measurement unit code for all weight parameters (default is 'lb')
     * @param string $length_code     Measurement unit code for all length parameters (default is 'in')
     */
    public function __construct($registry, $shipper_prefix = 'ups', $max_weight = 150, $max_length = 108, $max_size = 165, $weight_code = 'lb', $length_code = 'in') {
        parent::__construct(
            $max_weight, $max_length, $max_size, true,
            array( // init options
                'registry'       => $registry,
                'shipper_prefix' => $shipper_prefix,
                'weight_code'    => $weight_code,
                'length_code'    => $length_code
            )
        );
    }
    
    /**
     * @Override
     * @param array $init_options Requires the following entries:
     *              'registry'       => OpenCart Registry object
     *              'shipper_prefix' => String prefix used to retrieve shipper options from the config
     *              'length_code'    => Code for the length unit used by parameters, e.g. 'in'
     *              'weight_code'    => Code for the weight unit used by parameters, e.g. 'lb'
     */
    protected function init(array $init_options) {
        // Retrieve necessary objects from the init options array
        $registry = $init_options['registry'];
        $shipper_prefix = $init_options['shipper_prefix'];
        $config = $registry->get('config');
        // Setup measurement and weight units
        $this->length = $registry->get('length');
        $this->length_class_id = $config->get($shipper_prefix . '_length_class_id');
        $this->length_code = strtoupper($this->length->getUnit($this->length_class_id));
        $this->default_length_class_id = $this->getLengthCodeId($init_options['length_code']);
        $this->weight = $registry->get('weight');
        $this->weight_class_id = $config->get($shipper_prefix . '_weight_class_id');
        $this->weight_code = strtoupper($this->weight->getUnit($this->weight_class_id));
        $this->weight_code = ($this->weight_code == 'KG' ? 'KGS' : ($this->weight_code == 'LB' ? 'LBS' : $this->weight_code));
        $this->default_weight_class_id = $this->getWeightCodeId($init_options['weight_code']);
        // Setup remaining config-based fields
        $this->packaging_type = $this->getPackagingType($config, $shipper_prefix);
        $this->requires_insurance = ($config->has($shipper_prefix . '_insurance') ? $config->get($shipper_prefix . '_insurance') : null);
    }
    
    /**
     * @Override Converts value from the default currency to the store's currency
     */
    protected function getCurrencyValue($value) {
        return $this->getValidatedFloat($value); // TODO
    }
    
    /**
     * @Override Converts value from the default_length_class_id to the shipper's length_class_id
     */
    protected function getMeasurementValue($value) {
        return $this->length->convert($this->getValidatedFloat($value), $this->default_length_class_id, $this->length_class_id);
    }
    
    /**
     * @Override Converts value from the default_weight_class_id to the shipper's weight_class_id
     */
    protected function getWeightValue($value) {
        return $this->weight->convert($this->getValidatedFloat($value), $this->default_weight_class_id, $this->weight_class_id);
    }
    
    /**
     * @Override
     */
    protected function getPackageOptions($item) {
        $options = parent::getPackageOptions($item);
        if (!array_key_exists('type', $options) && $this->packaging_type !== null) {
            $options['type'] = $this->packaging_type;
        }
        if ($this->requires_insurance || array_key_exists('insured_amount', $options)) {
            $options['insured_amount'] = $item['total'];
        }
        return $options;
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
