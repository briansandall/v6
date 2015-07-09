<?php
/**
 * The Address class contains all information necessary to send or receive a shipment.
 * 
 * @package Awsp Shipping Package
 * @author Brian Sandall (adapted from Alex Fraundorf's original Awsp\Shipment.php implementation)
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Ship;

class Address
{
    protected $allowed = array('name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential');
    protected $data = array();

    /**
     * Constructs address object from the given array.
     * Required elements: 'address1', 'city', 'state', 'postal_code', 'country_code'
     * Allowed array elements: 'name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential'
     * @param $validateAsLabel If true, 'name' and 'phone' fields will also be required
     */
    public function __construct(array $data = array(), $validateAsLabel = true) {
        $this->data = $data;
        $this->sanitizeInput();
        $this->validate($validateAsLabel);
    }

    /**
     * Returns the field if it exists, otherwise returns an empty string.
     * Throws InvalidArgumentException if requested field is not one of the following:
     * 'name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential'
     */
    public function get($field) {
        if (false === array_search($field, $this->allowed)) {
            throw new \InvalidArgumentException("Requested '$field' is not a valid Address field.");
        }
        return (isset($this->data[$field]) ? $this->data[$field] : '');
    }

    /**
     * Applies basic filter to each of the address fields.
     */
    protected function sanitizeInput() {
        foreach($this->data as $key => $value) {
            if (false === array_search($key, $this->allowed)) {
                continue; // skip this entry
            }
            $value = trim($value);
            $value = filter_var($value, FILTER_SANITIZE_STRING);
            $value = substr($value, 0, 50);
            $this->data[$key] = (empty($value) ? null : $value);
        }
    }

    /**
     * Checks that the Address object is valid; if not, an exception is thrown.
     * @param $isLabel If true, 'name' and 'phone' fields will also be required
     * @throws UnexpectedValueException if Address object is not valid
     */
    protected function validate($isLabel) {
        $required_fields = array('address1', 'city', 'state', 'postal_code', 'country_code');
        if ($isLabel) {
            $required_fields = array_merge(array('name', 'phone'), $required_fields);
        }
        $invalid_properties = null;
        foreach ($required_fields as $field) {
            if ($this->data[$field] == null) {
                $invalid_properties .= $field . ', ';
            }
        }
        if (!empty($invalid_properties)) {
            throw new \UnexpectedValueException("Invalid Address object: required properties ($invalid_properties) are not set.");
        }
    }
}
