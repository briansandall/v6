<?php
/**
 * Constraint that checks if a value is an instance of the designated class.
 * If using PHP 7 or higher, anonymous classes may be a viable alternative to classes such as this.
 *
 * @package Awsp Constraint Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Constraint;

class TypeConstraint implements IConstraint
{
    protected $type;

    /**
     * @param string $type Fully qualified class name; the value must be an instance of this class
     */
    public function __construct($type) {
        $this->type = $type;
    }

    /**
     * @Override
     */
    public function check($value, &$error = '') {
        $real_type = (getType($value) === 'object' ? get_class($value) : getType($value));
        $error = "Expected type {$this->type}, received $real_type";
        return ($value instanceof $this->type);
    }
}
