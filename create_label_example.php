<?php
/**
 * Basic example usage of the AWSP Shipping class to create a shipping label(s).
 * 
 * @package Awsp Shipping Package
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

// display all errors while in development
error_reporting(E_ALL);
ini_set('display_errors', 'On');

// require the config file (autoloader file already included by config)
require_once('includes/config.php');

// The first order of business is to retrieve the ordered items and package them using
// the same packing algorithm used to generate the shipping quote.
// Items may be arrays or any custom Object type, usually as dictated by the choice
// of ECommerce framework. The default IPacker implementation expects an array type.

// An item with the minimum information required to be packable into a Package:
$item1 = array(
    'weight' => 11.34,
    'length' => 14.2,
    'width'  => 16.8,
    'height' => 26.34
);

// An item with some extra options, as well as in quantity:
$item2 = array(
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
$packer = new Ship\DefaultPacker($max_package_weight, $max_package_length, $max_package_size, false);

// Stores any items that could not be packed so we can display an error message to the user
$not_packed = array();

// Make the actual packages to ship
$packages = $packer->makePackages(array($item1, $item2), $not_packed);

// Exit with error message if any items could not be packed - these will need to
// have labels generated manually.
if (!empty($not_packed)) {
    $not_shipped = '';
    foreach ($not_packed as $p) {
        $not_shipped .= $p['name'];
    }
    exit("<p>Labels must be generated manually for the following items:</p><p><strong>$not_shipped</strong></p>");
}

//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// The $ship_to Address and $_GET are information you have received from your user. 
// Always validate and sanitize user input!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

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

// validate user input
// extract shipper
if(isset($_GET['shipper'])) {
    $shipper = filter_var($_GET['shipper'], FILTER_SANITIZE_STRING);
}
else {
    throw new \Exception('Missing required input (shipper).');
}

// extract service code
if(isset($_GET['service_code'])) {
    $service_code = filter_var($_GET['service_code'], FILTER_SANITIZE_STRING);
}
else {
    throw new \Exception('Missing required input (service_code).');
}


// create a Shipment object
try {
    $shipment = new Ship\Shipment($ship_to, $ship_from, $packages); 
}
// catch any exceptions 
catch(\Exception $e) {
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');    
}


// create the shipper object for the appropriate shipping vendor and pass it the shipment and config data
// using UPS
if($shipper == 'ups') {
    $ShipperObj = new Ship\Ups($shipment, $config);
}
// unrecognized shipper
else {
    throw new \Exception('Unrecognized shipper (' . $shipper . ').');
}

// send request for a shipping label(s)
try{
    // build parameters array to send to the createLabel method
    $params = array(
        'service_code' => $service_code
    );
    // call the createLabel method - a LabelResponse object will be returned unless there is an exception
    $Response = $ShipperObj->createLabel($params);
}
// display any caught exception messages
catch(\Exception $e){
    exit('<br /><br />Error: ' . $e->getMessage() . '<br /><br />');
}

// send opening html (note: this will not create a valid html document - for example only)
echo '<html><body>';

// format label(s) response
echo '
    <dl>
        <dt><strong>Status:</strong></dt>
        <dd>' . $Response->status . '</dd>
        <dt><strong>Shipment Cost:</strong></dt>
        <dd>$' . $Response->shipment_cost . '</dd>
        <dt><strong>Label(s):</strong></dt>
        <dd>
            <ol>
';

// loop through and display information for each label
foreach($Response->labels as $label){
    // output the label tracking number and image
    echo '
        <li>
            <ul>
                <li>Tracking Number: ' . $label['tracking_number'] . '</li>
                <li>';
    
                if($label['label_file_type'] == 'gif') {
                    echo '<img src="data:image/gif;base64, ' . $label['label_image'] . '" />';
                }
                
                echo '</li>
            </ul>
        </li>';
}

echo '
            </ol>
        </dd>
    </dl>
    <h5>Legal Disclaimer:
        <div>* UPS trademarks, logos and services are the property of United Parcel Service.</div>
    </h5>
    </body>
    </html>';