<?php
/**
 * Abstract packer class provides default implementation of IPacker#makePackages and requires
 * sub-classes to determine how each item is to be packaged. This allows each item to be any
 * type required by the individual software, instead of only allowing standard arrays.
 *
 * @package Awsp Packer Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Packer;

abstract class AbstractPacker implements IPacker
{
    /**
     * Array of all required \Awsp\Constraint\IConstraints, i.e. those that would cause a
     * carrier to refuse the package, such as by exceeding their max package weight limit.
     */
    protected $constraints = array();

    /**
     * Array of all optional \Awsp\Constraint\IConstraints, i.e. those that may incur
     * additional costs, but will not preclude the carrier from accepting the package.
     */
    protected $optional_constraints = array();

    /** Array of \Awsp\MergeStrategy\IMergeStrategy strategies available for merging packages */
    protected $merge_strategies = array();

    /** True if items passed to #getPackageWorker use a combined weight (item weight * quantity) */
    protected $is_weight_combined;

    /** Maximum weight for a single package */
    protected $max_weight;

    /** Maximum value for the longest dimension */
    protected $max_length;

    /** Maximum total size - total size equals the length plus twice the combined height and width */
    protected $max_size;

    /** Preferred maximum weight (e.g. to avoid additional handling fees) */
    protected $preferred_weight;

    /** Preferred maximum total size (e.g. to avoid additional handling fees) */
    protected $preferred_size;

    /** Constraint for package dimensions before additional handling fees are applied (may be NULL) */
    protected $handling_constraint = null;

    /**
     * Constructs a default packer with maximum allowed package weight, length, and size constraints.
     * Default values are in pounds and inches; use the same measurement unit as the items to ship.
     * @param float|int $max_weight The absolute maximum weight allowed for any one package
     * @param float|int $max_length The absolute maximum length (longest dimension) allowed
     * @param float|int $max_size   The absolute maximum total size allowed, where total size = length + (2 * width) + (2 * height)
     * @param boolean $is_weight_combined True if items passed to #getPackageWorker use a combined weight (item weight * quantity)
     * @param array $init_options   This parameter is passed to the #init method
     * @throws InvalidArgumentException if any argument fails to validate
     */
    public function __construct($max_weight = 150, $max_length = 108, $max_size = 165, $is_weight_combined = false, $init_options = array()) {
        // Allow sub-classes to do any necessary pre-initializations before value conversions
        $this->init($init_options);
        $this->max_weight = $this->getWeightValue($max_weight);
        $this->max_length = $this->getMeasurementValue($max_length);
        $this->max_size = $this->getMeasurementValue($max_size);
        $this->is_weight_combined = filter_var($is_weight_combined, FILTER_VALIDATE_BOOLEAN);
        // Set preferred values to max as defaults; don't add them as constraints at this point
        $this->preferred_weight = $this->max_weight;
        $this->preferred_size = $this->max_size;
        // Finally, add the default required constraints
        $this->addDefaultConstraints();
    }

    /**
     * Called from the constructor before any assignments are made, allowing sub-classes to
     * initialize any unit-conversion or other objects they may need, e.g. for #getMeasurementValue
     * @param array $init_options Contents vary based on the constructor, but may look like:
     *                          array('currency'=>'USD', 'measure'=>'in', 'weight'=>'lb')
     */
    protected function init(array $init_options) {}

    /**
     * Called at the end of the class constructor to add initial constraints. The default
     * implementation adds the minimum constraints required to ensure a deliverable package:
     *  - package type, to ensure subsequent constraints receive an \Awsp\Ship\Package object when checked
     *  - max weight, length, and size constraints, typically representing the limits of what a carrier will accept
     */
    protected function addDefaultConstraints() {
        // Package type constraint is added first, as subsequent constraints expect #check parameter to be that type
        $this->addConstraint(new \Awsp\Constraint\TypeConstraint('\Awsp\Ship\Package'));
        $this->addConstraint(new \Awsp\Constraint\PackageValueConstraint($this->max_weight, 'weight', '<='), 'max_weight', true, true);
        $this->addConstraint(new \Awsp\Constraint\PackageValueConstraint($this->max_length, 'length', '<='), 'max_length', true, true);
        $this->addConstraint(new \Awsp\Constraint\PackageValueConstraint($this->max_size, 'size', '<='), 'max_size', true, true);
    }

    /**
     * @Override Default implementation of IPacker#makePackages
     */
    public function makePackages(array $items, array &$notPacked = array()) {
        $packages = array();
        foreach ($items as $item) {
            try {
                $packed = $this->getPackageWorker($item, $packages);
                if (!is_array($packed)) {
                    $notPacked[] = $item;
                } else {
                    $packages = array_merge($packages, $packed);
                }
            } catch (\Exception $e) {
                $item['error'] = $e->getMessage(); // allows error message to be displayed
                $notPacked[] = $item;
            }
        }
        return $packages;
    }

    /**
     * Convert an item into one or more Packages, provided the item contains all valid
     * information (e.g. weight, dimensions, etc.) and that it fulfills all constraints.
     * @param $item Array or Object representing a single item, although that item may
     *               have a quantity greater than one
     * @param $packages Array of Package objects already packed so that the current item
     *               may attempt to merge with a previous package
     * @throws InvalidArgumentException if the item cannot be packaged for any reason
     * @throws UnexpectedValueException may be thrown when creating the Package
     * @return Array of \Awsp\Ship\Package objects to add, may be empty if item merged with $packages
     */
    protected abstract function getPackageWorker($item, array &$packages);

    /**
     * Allows sub-classes the opportunity to convert currency values used in other functions
     * @param float|int A currency value such as the value of a package in dollars
     * @return The converted value
     */
    public function getCurrencyValue($value) {
        return $this->getValidatedFloat($value);
    }

    /**
     * Allows sub-classes the opportunity to convert measurement values used in other functions
     * @param float|int A measurement value such as the length of a package in inches
     * @return The converted value
     */
    public function getMeasurementValue($value) {
        return $this->getValidatedFloat($value);
    }

    /**
     * Allows sub-classes the opportunity to convert weight values used in other functions
     * @param float|int A weight value such as the weight of a package in pounds
     * @return The converted value
     */
    protected function getWeightValue($value) {
        return $this->getValidatedFloat($value);
    }

    /**
     * Adds (optional) constraint for the preferred package size (e.g. to avoid additional handling fees)
     * @param float|int $size Usually the max size before a package is considered 'large'
     *                        Value is passed through #getMeasurementValue before it is used
     * @return Returns itself for convenience
     */
    public function setPreferredSize($size) {
        $this->preferred_size = $this->getMeasurementValue($this->getValidatedFloat($size));
        $this->addConstraint(new \Awsp\Constraint\PackageValueConstraint($this->preferred_size, 'size', '<='), 'preferred_size', false, true);
        return $this;
    }

    /**
     * Adds (optional) constraint for the preferred package weight (e.g. to avoid additional handling fees)
     * @param float|int $weight Usually the max weight before a package is considered 'heavy'
     *                          Value is passed through #getWeightValue before it is used
     * @return Returns itself for convenience
     */
    public function setPreferredWeight($weight) {
        if (!filter_var($weight, FILTER_VALIDATE_FLOAT)) {
            throw new \InvalidArgumentException("Expected float or integer for 'weight'; received " . getType($weight));
        }
        $this->preferred_weight = $this->getWeightValue($this->getValidatedFloat($weight));
        $this->addConstraint(new \Awsp\Constraint\PackageValueConstraint($this->preferred_weight, 'weight', '<='), 'preferred_weight', false, true);
        return $this;
    }

    /**
     * Adds or removes (optional) additional handling constraint based on the parameter
     * @param boolean $required True to add the constraint, false to remove it
     */
    protected function setAdditionalHandlingConstraint($required) {
        if ($required && $this->handling_constraint instanceof \Awsp\Constraint\IConstraint) {
            $this->addConstraint($this->handling_constraint, 'additional_handling', false, true);
        } else {
            unset($this->optional_constraints['additional_handling']);
        }
    }

    /**
     * Set the additional handling thresholds, but does not add the constraint
     * @param float|int $first  Maximum length of longest dimension before additional handling charges are applied
     * @param float|int $second Maximum length of second-longest dimension before additional handling charges are applied
     *                          Values are passed through #getMeasurementValue before they are used
     * @return Returns itself for convenience
     */
    public function setAdditionalHandlingLimits($first, $second) {
        $thresholds = array($this->getMeasurementValue($this->getValidatedFloat($first)),
                            $this->getMeasurementValue($this->getValidatedFloat($second)));
        $this->handling_constraint = new \Awsp\Constraint\PackageHandlingConstraint($thresholds);
        return $this;
    }

    /**
     * Adds (required) constraint for the maximum allowed insurance amount
     * @param float|int $value Value is passed through #getCurrencyValue before it is used
     * @return Returns itself for convenience
     */
    public function setMaxInsurance($value) {
        $value = $this->getCurrencyValue($this->getValidatedFloat($value));
        $this->addConstraint(new \Awsp\Constraint\PackageOptionConstraint($value, 'insured_amount', '<=', true), 'max_insurance', true, true);
        return $this;
    }

    /**
     * Override in child classes if the $item implementation differs from the default array
     * @param $item Array or Object containing information about the item(s) to be packaged
     * @return Array of options for a new package containing the specified item(s)
     */
    protected function getPackageOptions($item) {
        return (empty($item['options']) || !is_array($item['options']) ? array() : $item['options']);
    }

    /**
     * Override in child classes if the $item implementation differs from the default array
     * @param $item Array or Object, depending on the implementation
     * @return The quantity of the given item to be packaged (always at least 1)
     */
    protected function getQuantityFromItem($item) {
        if (array_key_exists('quantity', $item)) {
            return filter_var($item['quantity'], FILTER_VALIDATE_INT, array('options' => array('default' => 1, 'min_range' => 1)));
        }
        return 1;
    }

    /**
     * Returns numeric arguments as an array('length','width','height') sorted from highest to lowest
     */
    protected function getSortedDimensions($l, $w, $h) {
        $lwh = array($l, $w, $h);
        rsort($lwh, SORT_NUMERIC);
        return array_combine(array('length','width','height'), $lwh);
    }

    /**
     * Split an item into two, each with half the original quantity and any other
     * properties adjusted accordingly - usually used in combination with recursion.
     *
     * @param array|object $item The item to split must have a quantity greater than 1
     * @return Array containing exactly 2 items whose total quantity equals the original quantity
     * @throws InvalidArgumentException if item quantity is less than 2
     */
    protected function splitItem($item) {
        $quantity = $this->getQuantityFromItem($item);
        if ($quantity < 2) {
            throw new \InvalidArgumentException("Cannot split an item with quantity less than 2: item quantity = $quantity");
        }
        $tmp = array($item, $item);
        $tmp[0]['quantity'] = ceil($quantity / 2.0);
        $tmp[1]['quantity'] = $quantity - $tmp[0]['quantity'];
        return $tmp;
    }

    /**
     * Adds a merge strategy for use when combining items into previous packages.
     * Note that the IPacker implementation must support merging for this to have any effect.
     * @return Returns itself for convenience
     */
    public function addMergeStrategy(\Awsp\MergeStrategy\IMergeStrategy $strategy) {
        $this->merge_strategies[] = $strategy;
        return $this;
    }

    /**
     * Attempts to merge up to the given quantity of a package into existing packages.
     * Best to only call this method if there is at least one \Awsp\MergeStrategy\IMergeStrategy available.
     *
     * @param array   $packages    Array of \Awsp\Ship\Package packages from #getPackageWorker 
     * @param Package $single_item An \Awsp\Ship\Package package, usually representing quantity 1 of the item to be packed
     * @param int     $quantity    Quantity of the item to pack, usually retrieved from #getQuantityFromItem
     * @return int Remaining quantity after merge
     */
    protected function merge(array &$packages, \Awsp\Ship\Package $single_item, $quantity) {
        foreach ($packages as &$current_package) {
            while ($quantity > 0 && $this->mergePackage($current_package, $single_item, $this->merge_strategies)) {
                $quantity--;
            }
        }
        unset($current_package); // unset reference to save puppies
        return $quantity;
    }

    /**
     * Attempts to merge one package into another using the most efficient strategy provided.
     * The combined package must meet all required and optional constraints.
     *
     * @param \Awsp\Ship\Package $old  Reference to previously existing package - will be modified if merged
     * @param \Awsp\Ship\Package $item A package to be merged into the existing one
     * @param array        $strategies Any number of \Awsp\MergeStrategy\IMergeStrategy strategies to be attempted
     * @return True on success, otherwise false
     */
    protected function mergePackage(\Awsp\Ship\Package &$old, \Awsp\Ship\Package $item, array $strategies) {
        // If neither package requires additional handling, add the constraint; otherwise, remove it
        // Add additional handling constraint if the old package passes (i.e. it does not
        // already require additional handling); otherwise, ensure constraint is removed.
        if ($this->handling_constraint != null) {
            $this->setAdditionalHandlingConstraint($this->handling_constraint->check($old));
        }
        // Find the most efficiently packed package out of all available strategies
        $package = null;
        foreach ($strategies as $strategy) {
            if (!($strategy instanceof \Awsp\MergeStrategy\IMergeStrategy)) {
                // throw an exception to alert developers, or simply ignore it and continue on
                // throw new \InvalidArgumentException("Expected type \Awsp\MergeStrategy\IMergeStrategy, received " . getType($strategy));
                continue;
            }
            $combined = $this->getMergeResult($old, $item, $strategy);
            if ($combined) {
                if ($package == null || $package->get('size') > $combined->get('size')) {
                    $package = $combined;
                }
            }
        }
        if ($package instanceof \Awsp\Ship\Package) {
            $old = $package;
            return true;
        }
        return false;
    }

    /**
     * Attempts to merge the packages using the strategy provided, then checks
     * the resulting package against all required and optional constraints.
     * @return False if the packages could not be merged, or the merged \Awsp\Ship\Package package object
     */
    protected function getMergeResult(\Awsp\Ship\Package $old, \Awsp\Ship\Package $item, \Awsp\MergeStrategy\IMergeStrategy $strategy) {
        $combined = $strategy->merge($old, $item);
        if (!($combined instanceof \Awsp\Ship\Package)) {
            return false;
        } elseif (!$this->checkConstraints($combined) || !$this->checkOptionalConstraints($combined)) {
            return false;
        }
        return $combined;
    }

    /**
     * Adds a constraint, optionally overwriting any existing constraint with the same key.
     * A constraint should be considered 'required' if the shipping carrier would refuse a
     * non-conformant package, and 'optional' if it would simply incur an additional cost.
     *
     * @param IConstraint $constraint The constraint to add
     * @param int|string  $key        Optional key parameter used to access the constraint
     * @param boolean     $required   True if the constraint is required, or false for an optional constraint
     * @param boolean     $overwrite  True to overwrite any existing constraint
     * @throws InvalidArgumentException if a constraint exists for the provided key and $overwrite is false
     */
    public function addConstraint(\Awsp\Constraint\IConstraint $constraint, $key = null, $required = true, $overwrite = false) {
        if ($required) {
            $constraints =& $this->constraints;
        } else {
            $constraints =& $this->optional_constraints;
        }
        if ($key === null) {
            $constraints[] = $constraint;
        } elseif ($overwrite || !array_key_exists($key, $constraints)) {
            $constraints[$key] = $constraint;
        } else {
            throw new \InvalidArgumentException(($required ? 'Required' : 'Optional') . " constraint '$key' already exists!");
        }
    }

    /**
     * Checks whether or not the package fulfills all required constraints
     * @param Package $package The \Awsp\Ship\Package to be checked
     * @param string  $error   Message describing the constraint that failed, if any
     * @return True if the package fulfills all required constraints
     */
    protected function checkConstraints(\Awsp\Ship\Package $package, &$error = '') {
        return $this->doConstraintCheck($this->constraints, $package, $error);
    }

    /**
     * Checks whether or not the package fulfills all optional constraints, e.g. when merging packages
     * @param Package $package The \Awsp\Ship\Package to be checked
     * @param string  $error   Message describing the constraint that failed, if any
     * @return True if the package fulfills all optional constraints
     */
    protected function checkOptionalConstraints(\Awsp\Ship\Package $package, &$error = '') {
        return $this->doConstraintCheck($this->optional_constraints, $package, $error);
    }

    final private function doConstraintCheck(array $constraints, \Awsp\Ship\Package $package, &$error) {
        foreach ($constraints as $constraint) {
            if (!$constraint->check($package, $error)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns value as a float after validating with PHP's filter_var
     * @param float|int $value
     * @throws InvalidArgumentException if the value fails the filter
     */
    final protected function getValidatedFloat($value) {
        if (false === ($return = filter_var($value, FILTER_VALIDATE_FLOAT))) {
            throw new \InvalidArgumentException("Expected float or integer, received " . getType($value));
        }
        return $return;
    }
}
