<?php
/**
 * This constraint checks that a Package's length and width do not exceed the threshold at
 * which a mail carrier would charge additional handling fees. It should only be used if
 * neither the item nor the previous package require additional handling.
 *
 * @package Awsp Constraint Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 09/25/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Constraint;

class PackageHandlingConstraint implements IConstraint
{
    protected $bound;

    /**
     * @param $bound Array containing 2 float or integer values
     */
    public function __construct($bound) {
        if (count($bound) !== 2 || !filter_var($bound, FILTER_VALIDATE_FLOAT, FILTER_REQUIRE_ARRAY)) {
            throw new \InvalidArgumentException("PackageHandlingConstraint expects an array containing exactly 2 floats or integers");
        }
        rsort($bound); // remove any array keys and sort from highest to lowest
        $this->bound = $bound;
    }

    /**
     * @Override
     * @param $package Expected to be an \Awsp\Ship\Package object
     */
    public function check($package, &$error = '') {
        $error = "Package would require additional handling";
        return $package->get('length') <= $this->bound[0] && $package->get('width') <= $this->bound[1];
    }
}
