<?php
/**
 * Interface for any algorithm which packs products or smaller packages into packages for shipment.
 *
 * @package Awsp Shipping Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Ship;

interface IPacker {

    /**
     * Packs requested items into shippable packages.
     * @param array $items All items to be packaged; at a minimum, each entry must be able to provide
     *                     its weight, dimensions (length, width, height), and usually quantity
     * @param array &$notPacked Any items which can not be packed will be stored in this array
     * @return array of Awsp/Ship/Package objects, possibly empty if no items could be packaged
     */
    function makePackages(array $items, array &$notPacked = array());

}
