<?php
/**
 * AWSP UPS Module for CubeCart v6
 * ========================================
 * @author Brian Sandall
 * @copyright (c) 2015 Brian Sandall
 * @license GPL-3.0 http://opensource.org/licenses/GPL-3.0
 *
 * NOTE: The class name must match the module directory name,
 * so if the module was installed as 'Awsp_UPS', the following
 * 'class UPS' should be changed to 'class Awsp_UPS'.
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2014. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@devellion.com
 * License:  GPL-2.0 http://opensource.org/licenses/GPL-2.0
 */
use Awsp\Ship as Ship;
if (!defined('AWSP_ROOT_DIR')) { define('AWSP_ROOT_DIR', str_replace('\\', '/', CC_INCLUDES_DIR) . 'lib/Awsp/'); }
class UPS {
	private $_basket;
	private $_settings;
	private $_config; // config array for the actual AWSP library UPS shipping class
	
	public function __construct($basket = false) {
		$this->_db       =& $GLOBALS['db'];
		$this->_basket   = $basket;
		$this->_settings = $GLOBALS['config']->get(__CLASS__);
		$this->includeAutoloader();
		$this->_config = $this->getConfig();
	}

	protected function includeAutoloader() {
		require_once AWSP_ROOT_DIR . 'includes/autoloader.php';
	}

	/**
	 * Return config array for AWSP
	 */
	protected function getConfig() {
		$config = array(
			'production_status' => empty($this->_settings['test_mode']), // true for production or false for development
			'weight_unit'       => (strtoupper($GLOBALS['config']->get('config', 'product_weight_unit')) == 'KG' ? 'KG' : 'LB'),
			'dimension_unit'    => (strtoupper($GLOBALS['config']->get('config', 'product_measurement_unit')) == 'CM' ? 'CM' : 'IN'),
			'currency_code'     => $GLOBALS['config']->get('config', 'default_currency')
		);
		
		// Shipper information needs to match UPS records or the API call will fail
		$shipper_address = null;
		try {
			$shipper_address = new Ship\Address(
				array(
					'name'         => $GLOBALS['config']->get('config', 'store_name'),
					// Phone, Street, and City are not available from the default CubeCart install
					'phone'        => $this->_settings['account_phone'],
					'address1'     => $this->_settings['account_address_1'],
					'address2'     => $this->_settings['account_address_2'],
					'city'         => $this->_settings['account_city'],
					'state'        => getStateFormat($GLOBALS['config']->get('config', 'store_zone'), 'id', 'abbrev'),
					'postal_code'  => $GLOBALS['config']->get('config', 'store_postcode'),
					'country_code' => getCountryFormat($GLOBALS['config']->get('config','store_country'),'numcode','iso')
				)
			);
		} catch (\Exception $e) {
			// Force error to be logged so store owner can fix module settings (Smarty template masks any errors at this point)
			$message = "Awsp UPS shipping module store address settings do not appear to be valid - please check the settings in the admin control panel.";
			Database::getInstance()->insert('CubeCart_system_error_log', array('message' => $message, 'time' => time()));
		}

		// UPS shipper configuration settings
		// Sign up for credentials at: https://www.ups.com/upsdeveloperkit - Note: Chrome browser does not work for this page.
		$config['ups'] = array(
			'key'               => $this->_settings['accountKey'],
			'user'              => $this->_settings['accountUser'],
			'password'          => $this->_settings['accountPass'],
			'account_number'    => $this->_settings['accountNumber'],
			'testing_url'       => 'https://wwwcie.ups.com/webservices',
			'production_url'    => 'https://onlinetools.ups.com/webservices',
			// Absolute path to the UPS API files relative to the Ups.php file
			'path_to_api_files' => AWSP_ROOT_DIR . 'libs/Awsp/Ship/ups_api_files',
			'shipper_address'   => $shipper_address,
			'pickup_type'       => $this->getPickupType(),
			'rate_type'         => $this->getRateType()
		);
		return $config;
	}

	public function calculate() {
		return $this->request();
	}

	public function tracking($tracking_id = false) {
		return false;
	}

	/**
	 * Returns true if the UPS service is enabled
	 * @param $code UPS service code, e.g. '03' for standard Ground Service
	 */
	private function isServiceEnabled($code) {
		switch ($code) {
		case '01': return !empty($this->_settings['product1DA']); // Next Day Air
		case '02': return !empty($this->_settings['product2DA']); // 2nd Day Air
		case '03': return !empty($this->_settings['productGND']); // Ground
		case '07': return !empty($this->_settings['productXPR']); // Worldwide Express
		case '08': return !empty($this->_settings['productXPD']); // Worldwide Expeditor
		case '11': return !empty($this->_settings['productSTD']); // Canada Standard
		case '12': return !empty($this->_settings['product3DS']); // 3 Day Select
		case '13': return !empty($this->_settings['product1DP']); // Next Day Air Saver
		case '14': return !empty($this->_settings['product1DM']); // Next Day Air Early AM
		case '59': return !empty($this->_settings['product2DM']); // 2nd Day Air AM
		case '65': return !empty($this->_settings['productXDM']); // World Wide Saver
		default: return false;
		}
	}

	private function getPickupType() {
		$value = strtoupper($this->_settings['pickup_type']);
		switch ($value) {
		case 'RDP': return '01'; // Regular Daily Pickup
		case 'CC' : return '03'; // Customer Counter
		case 'OTP': return '06'; // One Time Pickup
		case 'OCA': return '07'; // On Call Air
		case 'LC' : return '19'; // Letter Center
		case 'ASC': return '20'; // Air Service Center
		default: throw new \InvalidArgumentException("Invalid pickup type setting: '$value'");
		}
	}

	private function getRateType() {
		$value = strtoupper($this->_settings['rate']);
		switch ($value) {
		case 'AR': return '00'; // Rates Associated with Shipper Number
		case 'DR': return '01'; // Daily Rates
		case 'RR': return '04'; // Retail Rates
		case 'SR': return '53'; // Standard List Rates
		default: throw new \InvalidArgumentException("Invalid rate setting: '$value'");
		}
	}

	/**
	 * Returns 1 if residential, or 0 for commercial
	 */
	private function isResidential(){
		return (strtoupper($this->_settings['rescom']) === 'COM' ? 0 : 1);
	}

	private function request() {
		if (!isset($this->_config['ups']['shipper_address'])) {
			return false; // allows page to display (with no rates) when shipper address not valid - exception already logged above
		}
		$packer = new \Awsp\Packer\Vendor\CubeCart\RecursivePacker(150, 108, 165, false);
		// Set default package dimensions based on store settings
		$packer->setDefaultDimensions(array($this->_settings['defaultPackageLength'], $this->_settings['defaultPackageWidth'], $this->_settings['defaultPackageHeight']));
		// Add store settings for per-package weight
		$packer->setPackagingWeight($this->_settings['packagingWeight']);
		// Add additional constraints
		$packer->setMaxInsurance(50000.00);
		$packer->setPreferredSize(129.999);
		$packer->setPreferredWeight(69.999);
		$packer->setAdditionalHandlingLimits(60, 30);
		// Add merge strategies
		$packer->addMergeStrategy(new \Awsp\MergeStrategy\DefaultMergeStrategy());
		$notPacked = array();
		try {
			$packages = $packer->makePackages($this->_basket['contents'], $notPacked);
		} catch (\Exception $e) {
			trigger_error('UPS Error: ' . $e->getMessage());
			return false;
		}
		// Exit with error message if any items could not be packed
		if (!empty($notPacked)) {
			$names = array();
			$errors = array();
			foreach ($notPacked as $p) {
				$names[] = $p['name'];
				if (!empty($p['error'])) {
					$errors[] = "[Item] $p[product_code] ($p[name]): [Error] $p[error]";
				}
			}
			if (!empty($errors)) {
				// Force error to be logged so store owner can fix product issues (Smarty template masks any errors at this point)
				$message = "Awsp UPS shipping errors:\n" . implode("\n", $errors);
				Database::getInstance()->insert('CubeCart_system_error_log', array('message' => $message, 'time' => time()));
			}
			return false;
		}
		$address = (isset($this->_basket['delivery_address'])) ? $this->_basket['delivery_address'] : $this->_basket['billing_address'];
		$customer = $this->_basket['customer'];
		$ship_to = new Ship\Address(
			array(
				'name' => (empty($address['title']) ? '' : $address['title'] . ' ') . $address['first_name'] . ' ' . $address['last_name'] . (empty($address['company_name']) ? '' : '<br>' . $address['company_name']),
				'attention' => (empty($address['attention']) ? '' : $address['attention']),
				'phone' => (empty($customer['mobile']) ? (empty($customer['phone']) ? '' : $customer['phone']) : $customer['mobile']),
				'email' => (empty($customer['email']) ? '' : $customer['email']),
				'address1' => (empty($address['line1']) ? /* Dummy value to avoid invalid \Address object */ 'Street' : $address['line1']),
				'address2' => (empty($address['line2']) ? '' : $address['line2']),
				'city' => (empty($address['town']) ? /* Dummy value to avoid invalid \Address object */ 'City' : $address['town']),
				'state' => $address['state_abbrev'],
				'postal_code' => $address['postcode'],
				'country_code' => $address['country_iso'],
				'is_residential' => (!empty($this->isResidential()))
			), false
		);
		$ship_from = null;
		try {
			$shipment = new Ship\Shipment($ship_to, $ship_from, $packages);
			$ups = new Ship\Ups($shipment, $this->_config);
			$rates = $ups->getRate();
		} catch (\Exception $e) {
			trigger_error('UPS Error: ' . $e->getMessage());
			return false;
		}
		$quote_data = array();
		if (empty($rates)) {
			trigger_error('UPS Error: No settings found in request function!');
			return false;
		} elseif ($rates->status != 'Success') {
			return false;
		}
		if (false === filter_var($this->_settings['handling'], FILTER_VALIDATE_FLOAT)) {
			$message = "Invalid value for AWSP UPS module Additional Handling Cost setting: {$this->_settings['handling']}";
			Database::getInstance()->insert('CubeCart_system_error_log', array('message' => $message, 'time' => time()));
			return false;
		} elseif (false === filter_var($this->_settings['handling_rate'], FILTER_VALIDATE_FLOAT)) {
			$message = "Invalid value for AWSP UPS module Shipping Rate Modifier setting: {$this->_settings['handling_rate']}";
			Database::getInstance()->insert('CubeCart_system_error_log', array('message' => $message, 'time' => time()));
			return false;
		}
		foreach ($rates->services as $service) {
			$code = $service['service_code'];
			$cost = $service['total_cost'];
			if (!($code && $cost)) {
				continue;
			}
			// Apply handling per package:
			$cost += ($this->_settings['handling'] > 0) ? ($service['package_count'] * $this->_settings['handling']) : 0;
			// Apply handling rate modifier (may be negative for a discount):
			$cost += $this->round_up($cost * $this->_settings['handling_rate'], 2);
			$currency = $service['currency_code'];
			// Set quote data for display:
			if ($this->isServiceEnabled($code)) {
				$quote_data[] = array(
					'id'		=> $code,
					'name'		=> $service['service_description'],
					'value'     => $cost,
					'tax_id'    => (int)$this->_settings['tax']
				);
			}
		}
		return $quote_data;
	}

	/** @author mvds from http://stackoverflow.com/questions/8771842/always-rounding-decimals-up-to-specified-precision */
	function round_up($in, $prec) {
		$fact = pow(10, $prec);
		return ceil($fact * $in) / $fact;
	}
}
?>
