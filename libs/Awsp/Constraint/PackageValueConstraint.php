<?php
/**
 * Compares the value from Package::get($key) against the given bound.
 *
 * @package Awsp Constraint Package
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Constraint;

class PackageValueConstraint implements IConstraint
{
    protected $bound;

    protected $key;

    protected $operator;

    protected $enabled = true;

    /**
     * @param mixed  $bound    Value to test against during the constraint check() function
     * @param string $key      \Awsp\Ship\Package property name to check, e.g. 'weight'
     * @param string $operator Logical operator, such that $value {$operator} $bound, e.g. $value <= $bound
     *                         Valid operators are '<=', '<', '>', '>=', '==', '!=', '===', and '!=='
     */
    public function __construct($bound, $key, $operator = '<=') {
        if (false === array_search($operator, array('<=', '<', '>', '>=', '==', '!=', '===', '!=='))) {
            throw new \InvalidArgumentException("Invalid operator '$operator'; valid operators are '<=', '<', '>', '>=', '==', '!=', '===', and '!=='");
        }
        $this->bound = $bound;
        $this->key = $key;
        $this->operator = $operator;
    }

    /**
     * @Override
     * @param $package Expected to be an \Awsp\Ship\Package object
     * @throws UnexpectedValueException if $this->key is not a valid \Awsp\Ship\Package property
     */
    public function check($package, &$error = '') {
        $error = "Package {$this->key} must be {$this->operator} {$this->bound}";
        return $this->compare($package->get($this->key), $this->bound);
    }

    /**
     * @Override
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * @Override
     */
    public function setStatus($is_enabled) {
        $this->enabled = (bool) $is_enabled;
    }

    /**
     * Compares the given value against the bound using the constraint's current $operator
     * @param mixed $bound May be the same as or derived from $this->bound
     * @return True if the comparison of the value against the bound is true
     * @throws InvalidArgumentException if $value is NULL or the operator is not recognized
     */
    protected function compare($value, $bound) {
        if ($value === null) {
            throw new \InvalidArgumentException("Value for key '{$this->key}' was NULL: invalid comparison");
        }
        switch ($this->operator) {
        case '<=' : return $value <= $bound;
        case '<'  : return $value < $bound;
        case '>'  : return $value > $bound;
        case '>=' : return $value >= $bound;
        case '==' : return $value == $bound;
        case '===': return $value === $bound;
        case '!=' : return $value != $bound;
        case '!==': return $value !== $bound;
        default: throw new \InvalidArgumentException("Invalid operator '$operator'; valid operators are '<=', '<', '>', '>=', '==', '!=', '===', and '!=='");
        }
    }
}
