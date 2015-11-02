<?php
/**
 * Abstraction layer for shipment objects passed to ShipperInterface#setShipment
 * 
 * @package Awsp Shipping Package
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Ship;

interface IShipment {

    /**
     * Return the Address to which this shipment is being sent
     * @return Awsp\Ship\Address
     */
    public function getShipToAddress();

    /**
     * Returns the Address from which the shipment is being sent if different from the shipper's address
     * @return Awsp\Ship\Address or possibly null if ship from address not specified
     */
    public function getShipFromAddress();

    /**
     * Add a package to this shipment
     */
    public function addPackage(Package $package);

    /**
     * @return array<Awsp\Ship\Package> All Packages in this shipment
     */
    public function getPackages();

}
