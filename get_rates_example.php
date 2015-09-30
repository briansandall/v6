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

// These are the default (UPS) measurements in LBS and INCHES; convert / change as needed
$max_package_weight = 150;
$max_package_length = 108;
$max_package_size   = 165;

// The default packing implementation provided packs all items separately, but the packing
// algorithm can be changed simply by changing this line to use a different IPacker implementation.
$packer = new Packer\DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false);

// Stores any items that could not be packed so we can display an error message to the user
$not_packed = array();

// Make the actual packages to ship
$packages = $packer->makePackages($items, $not_packed);

/*
// Example using vendor-based packing algorithms (OpenCart, in this case)
// Assumed that the following code is placed in OpenCart's shipping/ups.php model or its equivalent
// Note that the OpenCart packing implementations convert measurements based on store settings,
// so the constructor arguments do not need to be updated when those settings change.

// Choose a packing algorithm:
// 1. The same default packing algorithm as before but with OpenCart's measurement conversion capabilities
$packer = new Packer\Vendor\OpenCart\DefaultPacker($this->registry, 'ups', 150, 108, 165, 'lb', 'in');

// 2. A packing algorithm that packs as many of the same product into each package as possible without becoming oversize or overweight
$packer = (new Packer\Vendor\OpenCart\PackByProduct($this->registry, 'ups', 150, 108, 165, 'lb', 'in'))->setPreferredWeight(70, 'lb')->setPreferredSize(130, 'in');

// Then create packages using the shopping cart contents:
$packages = $packer->makePackages($this->cart->getProducts(), $notPacked);

// Update the AWSP $config array based on shipper / store settings so API request uses correct units
$config['weight_unit'] = strtoupper($this->registry->get('weight')->getUnit($this->config->get('ups_weight_class_id')));
$config['dimension_unit'] = strtoupper($this->registry->get('length')->getUnit($this->config->get('ups_length_class_id')));
$config['currency_code'] = strtoupper($this->registry->get('currency')->getCode());
*/

// Exit with error message if any items could not be packed - these need special attention
// or may not even be shippable. Customer may still order other items after removing the
// items listed here from their cart.
if (!empty($not_packed)) {
    $not_shipped = '';
    foreach ($not_packed as $p) {
        $not_shipped .= '<p><strong>' . (empty($p['name']) ? 'Unknown Item' : $p['name']) . '</strong>' . (empty($p['error']) ? '' : " - Error: $p[error]") . '</p>';
    }
    exit("<p>The following items are not eligible for shipping via UPS - please call to order:</p>$not_shipped");
}

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

// create a Shipment object with the provided addresses and packed items
try {
    $shipment = new Ship\Shipment($ship_to, $ship_from, $packages);
}
// catch any exceptions 
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
}

// UPS rates -----------------------------------------------------------------------------------------------------------
// interface with the desired shipper plugin object
try {
    // create the shipper object and pass it the Shipment object and config data array
    $Ups = new Ship\Ups($shipment, $config);
    // calculate rates for shipment - returns an instance of RatesResponse
    $rates = $Ups->getRate();
}
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');
}

// send opening html (note: this will not create a valid html document - for example only)
echo '<html><body>';

// output UPS rates response
echo '
    <h2>UPS (United Parcel Service)* Rates:</h2>
    <dl>
        <dt><strong>Status:</strong></dt>
        <dd>' . $rates->status . '</dd>
        <dt><strong>Rate Options:</strong></dt>
        <dd>
            <ol>
';

foreach($rates->services as $service) {
    // display the service, cost and a link to create the label
    echo '<li><strong>' . $service['service_description'] . '*: $' . $service['total_cost'] 
            . '</strong> - <a href="create_label_example.php?shipper=ups&service_code=' . $service['service_code'] 
            . '">Create Shipping Label(s)</a></li><ul>';
    // display any service specific messages
    echo '<li>Service Messages:<ul>';
    foreach($service['messages'] as $message) {
        echo '<li>' . $message . '</li>';
    }
    echo '
            </ul>
        </li>
    ';
    // display a break down of multiple packages if there are more than one
    if($service['package_count'] > 1) {
        echo '<li>Multiple Package Breakdown:<ul>';
        $counter = 1;
        foreach($service['packages'] as $package) {
            echo '<li>Package ' . $counter . ': $' . $package['total_cost'] . ' (Base: ' . $package['base_cost'] 
                    . ' + Options: ' . $package['option_cost'] . ')</li>';
            $counter++;
        }
        echo '
                        </ul>
                     </li>
        ';
    }
    echo '          </ul>';
}    
echo '
            </ol>
        </dd>
    </dl>';

echo '
    <h5>Legal Disclaimer:
        <div>* UPS trademarks, logos and services are the property of United Parcel Service.</div>
    </h5>
    </body>
    </html>';