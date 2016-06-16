<?php
/**
 * Shipping vendor class for YRC Freight.
 * 
 * @package Awsp Shipping Package
 * @author Brian Sandall
 * @copyright (c) 2016 Brian Sandall
 * @version 06/16/2016 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 * 
 * @copyright (c) YRC Freight API, documentation and logos are the property of YRC Freight.
 */
namespace Awsp\Ship;

class Yrc implements ShipperInterface
{
    /** @var array holder for config data (from includes/config.php) */
    protected $config = array();
    
    /** @var array the data to be sent to the YRC API */
    protected $request = array();
    
    /** @var object the Shipment object to process which contains Package object(s) */
    protected $shipment = null;
    
    /** @var object the API call response object */
    protected $response = null;
    
    /** @var array An array mapping YRC service request level codes to service description. */
    protected static $services = array(
        'ALL'  => 'Includes Standard, Guaranteed, and Expedited Services',
        'STD'  => 'Standard LTL',
        'GDEL' => 'Guaranteed Standard by 5 p.m.',
        'ACEL' => 'Accelerated',
        'TCS'  => 'Expedited Services', // queries all TCS subtypes A, P, and W
        'TCSA' => 'Time-Critical by Noon',
        'TCSP' => 'Time-Critical by 5 p.m.',
        'TCSW' => 'Time-Critical Hour Window',
        'FAF'  => 'Fast-as-Flite', // for intra-Canada
        'DEGP' => 'Dedicated Equipment by 5 p.m.',
    );
    
    /** @var array An array mapping YRC Price Option service class codes to service descriptions */
    protected static $service_classes = array(
        'ST' => 'Standard LTL',
        'GD' => 'Guaranteed Standard by 5 p.m.',
        'AC' => 'Accelerated',
        'AM' => 'Time-Critical by Noon',
        'PM' => 'Time-Critical by 5 p.m.',
        'HR' => 'Time-Critical Hour Window',
        'FF' => 'Fast-as-Flite Service',
    );
    
    /**
     * Constructor function - sets object properties
     * @param object \Awsp\Ship\IShipment $shipment any object implementing IShipment
     * @param array $config the configuration data
     */
    public function __construct(IShipment $shipment, array $config) {
        $this->setConfig($config);
        $this->setShipment($shipment);
        $this->request['LOGIN_USERID'] = $this->config['yrc']['user'];
        $this->request['LOGIN_PASSWORD'] = $this->config['yrc']['password'];
        $this->request['BusId'] = $this->config['yrc']['account_id'];
        $this->request['BusRole'] = $this->config['yrc']['account_role'];
        $this->request['PaymentTerms'] = $this->config['yrc']['account_terms'];
        $this->request['PickupDate'] = date('Ymd', strtotime('next week'));
        $this->request['TypeQuery'] = $this->config['yrc']['rate_type'];
        $this->request['ServiceClass'] = $this->config['yrc']['service_class'];
    }
    
    /**
     * Validate the config array and sets it as an object property
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function setConfig(array $config = array()) {
        if(!is_array($config) || empty($config)) {
            throw new \InvalidArgumentException('Config array is not valid.');
        }
        $this->config = $config;
    }
    
    /**
     * Sets the IShipment object for which rates or labels will be generated
     * @param \Awsp\Ship\IShipment $shipment
     */
    public function setShipment(IShipment $shipment) {
        $this->shipment = $shipment;
    }
    
    /**
     * Compiles the required information for obtaining a shipping rate quote into the YRC array and using sendRequest() 
     *      sends the request to the YRC API and returns a RateResponse object.
     * @return object \Awsp\Ship\RateResponse
     * @throws \Exception
     */
    public function getRate() {
        // extract shipper information from the config array
        $ship_from = $this->shipment->getShipFromAddress();
        $origin = ($ship_from instanceof Address ? $ship_from : $this->config['yrc']['shipper_address']);
        $this->request['OrigCityName'] = $origin->get('city');
        $this->request['OrigStateCode'] = $origin->get('state');
        $this->request['OrigZipCode'] = $origin->get('postal_code');
        $this->request['OrigNationCode'] = Address::formatCountryCode($origin->get('country_code'), 3);

        // extract receiver information from the Shipment object
        $destination = $this->shipment->getShipToAddress();
        $this->request['DestCityName'] = $destination->get('city');
        $this->request['DestStateCode'] = $destination->get('state');
        $this->request['DestZipCode'] = $destination->get('postal_code');
        $this->request['DestNationCode'] = Address::formatCountryCode($destination->get('country_code'), 3);
        
        // TODO account options:
        //$this->request['AccOptionCount'] = 1;
        //$this->request['AccOption1'] = 'NTFY'; // notify before delivery
        //$this->request['AccOption2'] = 'HOMD'; // residential delivery
        
        // retrieve packages and add them to the request data
        $packages = $this->shipment->getPackages();
        $this->request['LineItemCount'] = count($packages);
        // API will notify if too many line items; is it possible to split up shipments?
        if ($this->request['LineItemCount'] > 10) {
            //throw new \Exception('YRC Freight Rate API limited to a maximum of 10 packages at a time');
        }
        for ($i = 0; $i < $this->request['LineItemCount']; $i++) {
            $package = $packages[$i];
            $n = $i + 1;
            // NFMC codes may vary by item if the IPacker and back-end implementations support that
            $this->request["LineItemNmfcClass$n"] = ($package->getOption('nmfc_class') == null ? $this->config['yrc']['nmfc_class'] : $package->getOption('nmfc_class'));
            if (!empty($this->config['yrc']['nmfc_prefix']) || $package->getOption('nmfc_prefix') != null) {
                $this->request["LineItemNmfcPrefix$n"] = ($package->getOption('nmfc_prefix') == null ? $this->config['yrc']['nmfc_prefix'] : $package->getOption('nmfc_prefix'));
            }
            if (!empty($this->config['yrc']['nmfc_suffix']) || $package->getOption('nmfc_suffix') != null) {
                $this->request["LineItemNmfcSuffix$n"] = ($package->getOption('nmfc_suffix') == null ? $this->config['yrc']['nmfc_suffix'] : $package->getOption('nmfc_suffix'));
            }
            // set package type (e.g. 'PLT' for pallets)
            $this->request["LineItemPackageCode$n"] = ($package->getOption('type') == null ? $this->config['yrc']['package_type'] : $package->getOption('type'));
            // set the package's unit of dimensional measurement (inches is the default unit)
            $this->request["LineItemDimUom$n"] = ($this->config['dimension_unit'] == 'CM' ? 'CM' : 'IN');
            // set the package's dimensions and round each dimension up to the next whole number
            $this->request["LineItemPackageLength$n"] = ceil($package->get('length'));
            // NOTE: Yrc uses height as the 2nd largest dimension, rather than width, which is the opposite of how they are sorted in the Package class
            $this->request["LineItemPackageWidth$n"] = ceil($package->get('height')); // width set to 'height' value
            $this->request["LineItemPackageHeight$n"] = ceil($package->get('width')); // height set to 'width' value
            // set the package's unit of weight (pounds are the default unit)
            $this->request["LineItemWeightUom$n"] = ($this->config['weight_unit'] == 'KG' ? 'KG' : 'LB');
            // set the package's weight and round it up to the next whole number
            $this->request["LineItemWeight$n"] = ceil($package->get('weight'));
            // set the line item quantity if present
            if (filter_var($package->getOption('quantity'), FILTER_VALIDATE_INT)) {
                $this->request["LineItemHandlingUnits$n"] = $package->getOption('quantity');
                // YRC API expects combined weight, despite the field name being 'LineItemWeight'
                $this->request["LineItemWeight$n"] *= $package->getOption('quantity');
            }
        }
        // send the request - returns a standard object
        $this->response = $this->sendRequest();
        $status = $this->getResponseStatus();
        if ($status != 'Success') {
            throw new \Exception('YRC API encountered the following error: ' . $this->response['ReturnText']);
        }
        // create response object and populate with services
        $response = new RateResponse($status);
        $response->services = $this->getResponseRates();
        if (empty($response->services)) {
            throw new \Exception('YRC API did not return any valid service options for this shipment');
        }
        return $response;
    }
    
    /**
     * Sends the request to the YRC API server and converts the response into a standard object.
     * @param array $params optional parameters (not currently used)
     * @return object generated standard object containing the response
     * @throws \Exception exception if the request fails
     */
    protected function sendRequest(array $params=array()) {
        $url = ($this->config['production_status'] ? $this->config['yrc']['production_url'] : $this->config['yrc']['testing_url']);
        $url = sprintf("%s&%s", $url, http_build_query($this->request));
        $curl = curl_init($url);
        if ($curl !== false) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            if (($result = curl_exec($curl)) == false) {
                throw new \Exception('YRC API call failed: ' . curl_error($curl));
            }
            $response = new \SimpleXMLElement($result);
            curl_close($curl);
            if (!($response instanceof \SimpleXMLElement)) {
                throw new \Exception('YRC API response could not be parsed: ' . print_r($result, true));
            } elseif ($response['ReturnCode'] != '0000') {
                throw new \Exception('YRC API encountered the following error: ' . $response['ReturnText'] . ' | Error code: ' . $response['ReturnCode']);
            }
            return $response;
        } else {
            throw new \Exception('YRC API call failed: ' . curl_error($curl));
        }
    }
    
    /**
     * Extracts that status of the response and normalizes it.  Returns 'Success' or 'Error'
     * @return string 'Success' or 'Error'
     */
    protected function getResponseStatus() {
        return ($this->response['ReturnCode'] == '0000' ? 'Success' : 'Error');
    }
    
    /**
     * Extracts and returns rates for the services from the response object
     * @return array a multi-dimensional array containing the rate data for each service
     * @throws \UnexpectedValueException
     */
    protected function getResponseRates() {
        $options = array();
        switch ($this->request['TypeQuery']) {
        case 'MATRX': // may return object with multiple Tables each with their own TransitOptions, or a single object with TransitOptions
        case 'QUOTE':
            $rates = $this->response->BodyMain->RateQuote;
            if (isset($rates->QuoteMatrix)) { // MATRX (both STD and ALL) and QUOTE + ALL requests
                if (isset($rates->QuoteMatrix['TableCount'])) {
                    for ($i = 0; $i < $rates->QuoteMatrix['TableCount']; $i++) {
                        $options[] = $rates->QuoteMatrix->Table[$i]->TransitOptions;
                    }
                } else {
                    $options[] = $rates->QuoteMatrix->TransitOptions;
                }
            } else { // QUOTE + STD
                $options[] = $rates;
            }
            break;
        case 'TABLE':
            throw new \Exception('TABLE requests are not currently supported by this implementation');
        default: throw new \UnexpectedValueException('Valid request types are MATRX, QUOTE, or TABLE; received ' . $this->request['TypeQuery']);
        }
        if (empty($options)) {
            throw new \UnexpectedValueException('Failed to retrieve shipping rates from API.');
        }
        $output = array();
        $min = (isset($rates->QuoteMatrix->MinTransitDays) ? (int)$rates->QuoteMatrix->MinTransitDays : 0);
        foreach ($options as $option) {
            $n = count($option);
            for ($i = 0; $i < $n; $i++) {
                // TransitOptions->ErrorCode is not set for MATRX+STD
                if (!isset($option[$i]->ErrorCode) || (string)$option[$i]->ErrorCode === '0000') {
                    if (!isset($option[$i]->TransitDays) || (int)$option[$i]->TransitDays >= $min) {
                        $output[] = $this->getResponseRatesWorker($option[$i]);
                        /*
                        // YRC may return multiple rates of the same type based on days in transit;
                        // it may be useful to limit the output to one rate of each type, e.g. the cheapest:
                        $rate = $this->getResponseRatesWorker($option[$i]);
                        if (!array_key_exists($rate['service_code'], $output) || $rate['total_cost'] < $output[$rate['service_code']]['total_cost']) {
                            $output[$rate['service_code']] = $rate;
                        }
                        // However, filtering the returned rates is not the responsibilty of this class.
                        */
                    }
                }
            }
        }
        return $output;
    }
    
    /**
     * Extracts the data for a single rate service (used by getResponseRates)
     * @param object $rate is an object containing data for a single rate service
     * @return array containing the extracted data
     */
    protected function getResponseRatesWorker($rate) {
        $array = array();
        $array['messages'] = array();
        // ServiceReqLevel not included in response for STD service
        $array['service_code'] = (string)(empty($rate->ServiceReqLevel) ? 'STD' : $rate->ServiceReqLevel);
        // Multiple possible locations for price depending on rate request type:
        if (isset($rate->RatedCharges)) {
            $array['total_cost'] = (float)$rate->RatedCharges->TotalCharges / 100.00; // YRC returns rates in cents
        } elseif (isset($rate->TotalCharges)) {
            $array['total_cost'] = (float)$rate->TotalCharges / 100.00; // YRC returns rates in cents
        } elseif (isset($rate->PriceOption)) {
            $array['service_code'] = (string) $rate->PriceOption['Class'];
            $array['total_cost'] = (float)$rate->PriceOption->TotalCharges / 100.00; // YRC returns rates in cents
        } else { // not a valid rate, should have been an error message
            $array['total_cost'] = 0.00;
        }
        $array['service_description'] = 'YRC ' . (array_key_exists($array['service_code'], Yrc::$services) ? Yrc::$services[$array['service_code']] : Yrc::$service_classes[$array['service_code']]);
        $array['currency_code'] = (string) $this->response->BodyMain->RateQuote->Currency;
        return $array;
    }
    
    /**
     * @deprecated YRC does not have a label API at this time
     */
    public function createLabel(array $params=array()) {
        throw new \Exception('Label creation is not currently supported.');
    }
}
