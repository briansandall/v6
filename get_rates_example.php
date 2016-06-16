<?php
/**
 * Basic example usage of the AWSP Shipping class to obtain rates.
 * 
 * @author Brian Sandall (originally by Alex Fraundorf - AlexFraundorf.com)
 * @copyright (c) 2015 Brian Sandall
 * @copyright (c) 2012-2013, Alex Fraundorf and AffordableWebSitePublishing.com LLC
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @since 12/02/2012
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 */
use \Awsp\Ship as Ship;
use \Awsp\Packer as Packer;

// display all errors while in development
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// require the config file (autoloader file already included by config)
require_once('includes/config.php');

// The first order of business is to retrieve all ordered items and package them.
// Items may be arrays or any custom Object type, usually as dictated by the choice
// of ECommerce framework. The default IPacker implementation expects an array type.
// Normally the items to ship are retrieved from the user's shopping cart.
$items = array();

// An item with the minimum information required to be packable into a Package:
$items[] = array(
    'weight' => 11.34,
    'length' => 14.2,
    'width'  => 16.8,
    'height' => 26.34
);

// An item with some extra options, as well as in quantity:
$items[] = array(
    'weight'   => 24,
    'length'   => 10,
    'width'    => 6,
    'height'   => 12,
    'quantity' => 3,
    'options'  => array(
        'signature_required' => true, 
        'insured_amount'     => 274.95
    )
);

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// The $ship_to Address is information you have received from your user.  Always validate and sanitize user input!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

// if ship_from is null, the default shipper address from the config is used
$ship_from = null;
$ship_to = new Ship\Address(array(
        'name'           => 'XYZ Corporation',
        'attention'      => 'Attn: Bill',
        'phone'          => '555-123-4567',
        'email'          => '',
        'address1'       => '2 Massachusetts Ave NE',
        'address2'       => 'Suite 100',
        'address3'       => 'Room 5C', // not supported by all shippers
        'city'           => 'Washington',
        'state'          => 'DC',
        'postal_code'    => '20212',
        'country_code'   => 'US',
        'is_residential' => false
    )
);

// Array of shipping plugins and their packers that will be used to fetch rates
$shippers = array();

//==========================================================================//
// UPS packer setup
//==========================================================================//
// These are the default (UPS) measurements in LBS and INCHES; convert / change as needed
$max_package_weight = 150;
$max_package_length = 108;
$max_package_size   = 165;

// The default packing implementation provided packs all items separately, but the packing
// algorithm can be changed simply by changing this line to use a different IPacker implementation.
$packer = new Packer\DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false);

// For example:
$packer = new Packer\RecursivePacker($max_package_weight, $max_package_length, $max_package_size, false);

///////////////////////////////////////////////////////////////////////////////
// OPTIONAL: Additional setup for the IPacker object
// NOTE that the following methods belong to the AbstractPacker class, NOT the IPacker
// interface, so they may need to change or be removed for custom IPacker implementations
///////////////////////////////////////////////////////////////////////////////

// Add additional constraints that may cause the shipper to refuse the package
$packer->addConstraint(new \Awsp\Constraint\PackageOptionConstraint($packer->getCurrencyValue(50000.00), 'insured_amount', '<=', true), 'max_insurance', true, true);

// Add additional constraints to avoid extra charges when merging packages
// Package considered 'large' if 130 inches or more in total size
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(130), 'size', '<'), 'preferred_size', false, true);
// Package considered 'large' if 70 lbs or more
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(70), 'weight', '<'), 'preferred_weight', false, true);
// Package incurs additional handling fees if longest dimension over 60 inches or second-longest dimension over 30
$packer->addConstraint(new \Awsp\Constraint\PackageHandlingConstraint(array($packer->getMeasurementValue(60), $packer->getMeasurementValue(30))), 'additional_handling', false, true);

// Add one or more merge strategies if desired and the IPacker supports it
$packer->addMergeStrategy(new \Awsp\MergeStrategy\DefaultMergeStrategy());

// Add UPS packer to array
$shippers['Ups'] = array('name'=>'UPS', 'packer'=>$packer);

/*
// Example using vendor-based packing algorithms (OpenCart, in this case)
// Assumed that the following code is placed in OpenCart's shipping/ups.php model or its equivalent
// Note that the OpenCart packing implementations convert measurements based on store settings,
// so the constructor arguments do not need to be updated when those settings change.

// Choose a packing algorithm:
// 1. The same default packing algorithm as before but with OpenCart's measurement conversion capabilities
$packer = new Packer\Vendor\OpenCart\DefaultPacker($this->registry, 'ups', 150, 108, 165, 'lb', 'in');

// 2. A packing algorithm that packs as many of the same product into each package as possible without becoming oversize or overweight
$packer = (new Packer\Vendor\OpenCart\PackByProduct($this->registry, 'ups', 150, 108, 165, 'lb', 'in'));

// Perform any optional steps here, such as adding additional constraints or merge strategies
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(130), 'size', '<'), 'preferred_size', false, true);
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(70), 'weight', '<'), 'preferred_weight', false, true);
$packer->addMergeStrategy(new \Awsp\MergeStrategy\DefaultMergeStrategy());

// Then create packages using the shopping cart contents:
$packages = $packer->makePackages($this->cart->getProducts(), $notPacked);

// Update the AWSP $config array based on shipper / store settings so API request uses correct units
$config['weight_unit'] = strtoupper($this->registry->get('weight')->getUnit($this->config->get('ups_weight_class_id')));
$config['dimension_unit'] = strtoupper($this->registry->get('length')->getUnit($this->config->get('ups_length_class_id')));
$config['currency_code'] = strtoupper($this->registry->get('currency')->getCode());
*/
//==========================================================================//
// YRC Freight quote uses a different packer setup
//==========================================================================//
// Effective maximum weight and dimensions for YRC rate quote API
$max_package_weight = 5000; // note that this is per single package
$max_package_length = 326;
$max_package_width  = 103; // note that this is used as maximum height on YRC's website
$max_package_height =  92; // note that this is used as maximum width on YRC's website
$max_package_size   = $max_package_length + 2 * ($max_package_width + $max_package_height);

// Create a new packer with YRC's constraints
$packer = new Packer\RecursivePacker($max_package_weight, $max_package_length, $max_package_size, false);

// YRC additional hard dimension limits: height <= 103" and width <= 92"
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue($max_package_height), 'height', '<='), 'max_height', true, true);
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue($max_package_width), 'width', '<='), 'max_width', true, true);

// Add a soft constraint for max length and width based on size of standard pallet
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(48), 'length', '<='), 'preferred_length', false, true);
$packer->addConstraint(new \Awsp\Constraint\PackageValueConstraint($packer->getMeasurementValue(40), 'width', '<='), 'preferred_width', false, true);

// Add one or more merge strategies if desired and the IPacker supports it
$packer->addMergeStrategy(new \Awsp\MergeStrategy\DefaultMergeStrategy());

// Add YRC packer to array
$shippers['Yrc'] = array('name'=>'YRC Freight', 'packer'=>$packer);

//==========================================================================//
// Iterate over shipping plugins and fetch rates
//==========================================================================//
foreach ($shippers as $key => $shipper) {
    // Stores any items that could not be packed so we can display an error message to the user
    $not_packed = array();
    try {
        // Make the actual packages to ship
        $packages = $shipper['packer']->makePackages($items, $not_packed);
        // If all items could be packed, create the shipment and fetch rates
        if (empty($not_packed)) {
            // create a Shipment object with the provided addresses and packed items
            $shipment = new Ship\Shipment($ship_to, $ship_from, $packages);
            // create the shipper object and pass it the Shipment object and config data array
            $plugin_class = "\\Awsp\\Ship\\$key";
            $plugin = new $plugin_class($shipment, $config);
            // calculate rates for shipment - returns an instance of RatesResponse
            $shippers[$key]['rates'] = $plugin->getRate();
        } else {
            // Display error message if any items could not be packed - these need special attention
            // or may not even be shippable. Customer may still order other items after removing the
            // items listed here from their cart.
            $not_shipped = '';
            foreach ($not_packed as $p) {
                $not_shipped .= '<p><strong>' . (empty($p['name']) ? 'Unknown Item' : $p['name']) . '</strong>' . (empty($p['error']) ? '' : " - Error: $p[error]") . '</p>';
            }
            $shippers[$key]['error'] = "<p>The following items are not eligible for shipping via $shipper[name] - please call to order:</p>$not_shipped";
        }
    } catch(\Exception $e) {
        $shippers[$key]['error'] = '<p>Error: ' . $e->getMessage() . '</p>';
    }
}

//==========================================================================//
//                            DISPLAY RESULTS                               //
//==========================================================================//
// send opening html (note: this will not create a valid html document - for example only)
echo '<html><body>';

// output shipper rates responses
foreach ($shippers as $shipper) {
    // display any error messages from processing stages
    if (!empty($shipper['error'])) {
        echo '<h2>' . $shipper['name'] . '* Error:</h2>' . $shipper['error'];
        continue;
    }
    echo '
        <h2>' . $shipper['name'] . '* Rates:</h2>
        <dl>
            <dt><strong>Status:</strong></dt>
            <dd>' . $shipper['rates']->status . '</dd>
            <dt><strong>Rate Options:</strong></dt>
            <dd>
                <ol>
    ';
    foreach ($shipper['rates']->services as $service) {
        // display the service, cost and a link to create the label
        echo '<li><strong>' . $service['service_description'] . '*: $' . sprintf('%.2F', $service['total_cost']) 
                . '</strong> - <a href="create_label_example.php?shipper=ups&service_code=' . $service['service_code'] 
                . '">Create Shipping Label(s)</a></li><ul>';
        // display any service specific messages
        if (!empty($service['messages'])) {
            echo '<li>Service Messages:<ul>';
            foreach($service['messages'] as $message) {
                echo '<li>' . $message . '</li>';
            }
            echo '</ul></li>';
        }
        // display a break down of multiple packages if there are more than one
        if (!empty($service['packages']) && $service['package_count'] > 1) {
            echo '<li>Multiple Package Breakdown:<ul>';
            $counter = 1;
            foreach($service['packages'] as $package) {
                echo '<li>Package ' . $counter . ': $' . sprintf('%.2F', $package['total_cost']) . ' (Base: ' . $package['base_cost'] 
                        . ' + Options: ' . sprintf('%.2F', $package['option_cost']) . ')</li>';
                $counter++;
            }
            echo '</ul></li>';
        }
        echo '</ul>';
    }    
    echo '
                </ol>
            </dd>
        </dl>';
}
echo '
    <h5>Legal Disclaimer:
        <div>* Shipping company trademarks, logos and services are the property of their respective companies.</div>
    </h5>
    </body>
</html>';
