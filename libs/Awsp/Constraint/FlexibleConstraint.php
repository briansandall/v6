<?php
/**
 * Constraint that checks one or more child constraints, passing if any one of them succeeds.
 *
 * @package Awsp Constraint Package
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/28/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Constraint;

class FlexibleConstraint implements IConstraint
{
    protected $children;

    protected $enabled = true;

    /**
     * @param array $children Array of IConstraints to be checked
     */
    public function __construct(array $children) {
        if (empty($children)) {
            throw new \InvalidArgumentException("FlexibleConstraint requires at least one child constraint");
        }
        foreach ($children as $child) {
            if (!($child instanceof IConstraint)) {
                throw new \InvalidArgumentException("All array elements must be of type IConstraint; received " . getType($child));
            }
        }
        $this->children = $children;
    }

    /**
     * @Override
     */
    public function check($package, &$error = '') {
        foreach ($this->children as $child) {
            if ($child->check($package, $error)) {
                return true;
            }
        }
        return false;
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
}
