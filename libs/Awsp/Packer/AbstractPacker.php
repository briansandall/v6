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
    /** Maximum weight for a single package */
    protected $max_weight;

    /** Maximum value for the longest dimension */
    protected $max_length;

    /** Maximum total size - total size equals the length plus twice the combined height and width */
    protected $max_size;

    /**
     * Constructs a default packer with maximum allowed package weight and dimensions.
     * Default values are in pounds and inches; use the same measurement unit as the items to ship.
     * @param integer $max_weight The absolute maximum weight allowed for any one package
     * @param integer $max_length The absolute maximum length (longest dimension) allowed
     * @param integer $max_size   The absolute maximum total size allowed, where total size = (length + (2 * (width + height)))
     * @throws InvalidArgumentException if any argument fails to validate
     */
    public function __construct($max_weight = 150, $max_length = 108, $max_size = 165) {
        $this->max_weight = filter_var($max_weight, FILTER_VALIDATE_INT);
        $this->max_length = filter_var($max_length, FILTER_VALIDATE_INT);
        $this->max_size = filter_var($max_size, FILTER_VALIDATE_INT);
        if (!is_int($this->max_weight)) {
            throw new \InvalidArgumentException("Expected integer for 'max_weight'; received " . getType($max_weight));
        } elseif (!is_int($this->max_length)) {
            throw new \InvalidArgumentException("Expected integer for 'max_length'; received " . getType($max_length));
        } elseif (!is_int($this->max_size)) {
            throw new \InvalidArgumentException("Expected integer for 'max_size'; received " . getType($max_size));
        }
    }

    /**
     * @Override Default implementation of IPacker#makePackages
     */
    public function makePackages(array $items, array &$notPacked = array()) {
        $packages = array();
        foreach ($items as $item) {
            try {
                $packed = $this->getPackageWorker($item);
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
     * @throws InvalidArgumentException if the item cannot be packaged for any reason
     * @throws UnexpectedValueException may be thrown when creating the Package
     * @return Array of one or more Awsp\Ship\Package objects
     */
    protected abstract function getPackageWorker($item);

}
