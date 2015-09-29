<?php
/**
 * Generic interface to validate a value against a specific requirement, i.e. a constraint.
 *
 * @package Awsp Constraint Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Constraint;

interface IConstraint
{
    /**
     * Check whether the given value meets the constraint requirements
     * @param mixed $value May be of any type, depending on the constraint
     * @param mixed $error When provided, it typically stores a string describing the reason for failure, if any
     * @return True if the value passes the constraint, or false if it fails
     */
    function check($value, &$error = '');

}
