<?php
/**
 * The Address class contains all information necessary to send or receive a shipment.
 * 
 * @package Awsp Shipping Package
 * @author Brian Sandall (adapted from Alex Fraundorf's original Awsp\Shipment.php implementation)
 * @copyright (c) 2015 Brian Sandall
 * @version 07/07/2015 - NOTICE: This is beta software.  Although it has been tested, there may be bugs and 
 *      there is plenty of room for improvement.  Use at your own risk.
 * @license MIT License http://www.opensource.org/licenses/mit-license.php
 */
namespace Awsp\Ship;

class Address
{
    /**
     * Convert given country code to new format, if possible
     * @param string     $code  original country code, e.g. 'US'
     * @param int|string $alpha ISO 3166-1 alpha designation; acceptable values are 2 and 3
     * @return Country code with the specified number of characters
     * @throws InvalidArgumentException if country code not found in the lookup table or number of characters invalid
     */
    public static function formatCountryCode($code, $alpha = 2) {
        $code = strtoupper($code);
        if ($alpha != 2 && $alpha != 3) {
            throw new \InvalidArgumentException("Valid values for alpha are 2 and 3. Received " . print_r($alpha, true));
        } elseif (strlen($code) == $alpha) {
            return $code;
        } elseif ($alpha == 3 && array_key_exists($code, Address::$COUNTRY_CODES)) {
            return Address::$COUNTRY_CODES[$code];
        } elseif ($result = array_search($code, array_flip(Address::$COUNTRY_CODES), true) !== false) {
            return Address::$COUNTRY_CODES[array_keys(Address::$COUNTRY_CODES)[$result]];
        }
        throw new \InvalidArgumentException("Failed to convert country code $code to $alpha characters in length");
    }

    protected $allowed = array('name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential');
    protected $data = array();

    /**
     * Constructs address object from the given array.
     * Required elements: 'address1', 'city', 'state', 'postal_code', 'country_code'
     * Allowed array elements: 'name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential'
     * @param $validateAsLabel If true, 'name' and 'phone' fields will also be required
     */
    public function __construct(array $data = array(), $validateAsLabel = true) {
        $this->data = $data;
        $this->sanitizeInput();
        $this->validate($validateAsLabel);
    }

    /**
     * Returns the field if it exists, otherwise returns an empty string.
     * Throws InvalidArgumentException if requested field is not one of the following:
     * 'name','attention','phone','email','address1','address2','address3','city','state','postal_code','country_code','is_residential'
     */
    public function get($field) {
        if (false === array_search($field, $this->allowed)) {
            throw new \InvalidArgumentException("Requested '$field' is not a valid Address field.");
        }
        return (isset($this->data[$field]) ? $this->data[$field] : '');
    }

    /**
     * Applies basic filter to each of the address fields.
     */
    protected function sanitizeInput() {
        foreach($this->data as $key => $value) {
            if (false === array_search($key, $this->allowed)) {
                continue; // skip this entry
            }
            $value = trim($value);
            $value = filter_var($value, FILTER_SANITIZE_STRING);
            $value = substr($value, 0, 50);
            $this->data[$key] = (empty($value) ? null : $value);
        }
    }

    /**
     * Checks that the Address object is valid; if not, an exception is thrown.
     * @param $isLabel If true, 'name' and 'phone' fields will also be required
     * @throws UnexpectedValueException if Address object is not valid
     */
    protected function validate($isLabel) {
        $required_fields = array('address1', 'city', 'state', 'postal_code', 'country_code');
        if ($isLabel) {
            $required_fields = array_merge(array('name', 'phone'), $required_fields);
        }
        $invalid_properties = null;
        foreach ($required_fields as $field) {
            if ($this->data[$field] == null) {
                $invalid_properties .= $field . ', ';
            }
        }
        if (!empty($invalid_properties)) {
            throw new \UnexpectedValueException("Invalid Address object: required properties ($invalid_properties) are not set.");
        }
    }

    /** Map of 2-character to 3-character country codes */
    public static $COUNTRY_CODES = array(
        'AD'=>'AND',
        'AE'=>'ARE',
        'AF'=>'AFG',
        'AG'=>'ATG',
        'AI'=>'AIA',
        'AL'=>'ALB',
        'AN'=>'ANT',
        'AQ'=>'ATA',
        'AR'=>'ARG',
        'AS'=>'ASM',
        'AT'=>'AUT',
        'AU'=>'AUS',
        'AW'=>'ABW',
        'AZ'=>'AZE',
        'BA'=>'BIH',
        'BB'=>'BRB',
        'BD'=>'BGD',
        'BE'=>'BEL',
        'BF'=>'BFA',
        'BG'=>'BGR',
        'BH'=>'BHR',
        'BI'=>'BDI',
        'BJ'=>'BEN',
        'BM'=>'BMU',
        'BN'=>'BRN',
        'BO'=>'BOL',
        'BR'=>'BRA',
        'BS'=>'BHS',
        'BT'=>'BTN',
        'BV'=>'BVT',
        'BW'=>'BWA',
        'BY'=>'BLR',
        'BZ'=>'BLZ',
        'CA'=>'CAN',
        'CC'=>'CCK',
        'CD'=>'COD',
        'CF'=>'CAF',
        'CG'=>'COG',
        'CH'=>'CHE',
        'CI'=>'CIV',
        'CK'=>'COK',
        'CL'=>'CHL',
        'CM'=>'CMR',
        'CN'=>'CHN',
        'CO'=>'COL',
        'CR'=>'CRI',
        'CU'=>'CUB',
        'CV'=>'CPV',
        'CX'=>'CXR',
        'CY'=>'CYP',
        'CZ'=>'CZE',
        'DE'=>'DEU',
        'DJ'=>'DJI',
        'DK'=>'DNK',
        'DM'=>'DMA',
        'DO'=>'DOM',
        'DZ'=>'DZA',
        'EC'=>'ECU',
        'EE'=>'EST',
        'EG'=>'EGY',
        'EH'=>'ESH',
        'ER'=>'ERI',
        'ES'=>'ESP',
        'ET'=>'ETH',
        'FI'=>'FIN',
        'FJ'=>'FJI',
        'FK'=>'FLK',
        'FM'=>'FSM',
        'FO'=>'FRO',
        'FR'=>'FRA',
        'GA'=>'GAB',
        'GB'=>'GBR',
        'GD'=>'GRD',
        'GE'=>'GEO',
        'GF'=>'GUF',
        'GG'=>'GGY',
        'GH'=>'GHA',
        'GI'=>'GIB',
        'GL'=>'GRL',
        'GM'=>'GMB',
        'GN'=>'GIN',
        'GP'=>'GLP',
        'GQ'=>'GNQ',
        'GR'=>'GRC',
        'GS'=>'SGS',
        'GT'=>'GTM',
        'GU'=>'GUM',
        'GW'=>'GNB',
        'GY'=>'GUY',
        'HK'=>'HKG',
        'HM'=>'HMD',
        'HN'=>'HND',
        'HR'=>'HRV',
        'HT'=>'HTI',
        'HU'=>'HUN',
        'ID'=>'IDN',
        'IE'=>'IRL',
        'IL'=>'ISR',
        'IN'=>'IND',
        'IO'=>'IOT',
        'IQ'=>'IRQ',
        'IR'=>'IRN',
        'IS'=>'ISL',
        'IT'=>'ITA',
        'JE'=>'JEY',
        'JM'=>'JAM',
        'JO'=>'JOR',
        'JP'=>'JPN',
        'KE'=>'KEN',
        'KG'=>'KGZ',
        'KH'=>'KHM',
        'KI'=>'KIR',
        'KM'=>'COM',
        'KN'=>'KNA',
        'KP'=>'PRK',
        'KR'=>'KOR',
        'KW'=>'KWT',
        'KY'=>'CYM',
        'KZ'=>'KAZ',
        'LA'=>'LAO',
        'LB'=>'LBN',
        'LC'=>'LCA',
        'LI'=>'LIE',
        'LK'=>'LKA',
        'LS'=>'LSO',
        'LT'=>'LTU',
        'LU'=>'LUX',
        'LV'=>'LVA',
        'LY'=>'LBY',
        'MA'=>'MAR',
        'MC'=>'MCO',
        'MD'=>'MDA',
        'ME'=>'MNE',
        'MG'=>'MDG',
        'MH'=>'MHL',
        'MK'=>'MKD',
        'ML'=>'MLI',
        'MM'=>'MMR',
        'MN'=>'MNG',
        'MO'=>'MAC',
        'MP'=>'MNP',
        'MQ'=>'MTQ',
        'MR'=>'MRT',
        'MS'=>'MSR',
        'MT'=>'MLT',
        'MU'=>'MUS',
        'MV'=>'MDV',
        'MW'=>'MWI',
        'MX'=>'MEX',
        'MY'=>'MYS',
        'MZ'=>'MOZ',
        'NA'=>'NAM',
        'NC'=>'NCL',
        'NE'=>'NER',
        'NF'=>'NFK',
        'NG'=>'NGA',
        'NI'=>'NIC',
        'NL'=>'NLD',
        'NO'=>'NOR',
        'NP'=>'NPL',
        'NR'=>'NRU',
        'NU'=>'NIU',
        'NZ'=>'NZL',
        'OM'=>'OMN',
        'PA'=>'PAN',
        'PE'=>'PER',
        'PF'=>'PYF',
        'PG'=>'PNG',
        'PH'=>'PHL',
        'PK'=>'PAK',
        'PL'=>'POL',
        'PM'=>'SPM',
        'PN'=>'PCN',
        'PR'=>'PRI',
        'PS'=>'PSE',
        'PT'=>'PRT',
        'PW'=>'PLW',
        'PY'=>'PRY',
        'QA'=>'QAT',
        'RE'=>'REU',
        'RO'=>'ROM',
        'RS'=>'SRB',
        'RU'=>'RUS',
        'RW'=>'RWA',
        'SA'=>'SAU',
        'SB'=>'SLB',
        'SC'=>'SYC',
        'SD'=>'SDN',
        'SE'=>'SWE',
        'SG'=>'SGP',
        'SH'=>'SHN',
        'SI'=>'SVN',
        'SJ'=>'SJM',
        'SK'=>'SVK',
        'SL'=>'SLE',
        'SM'=>'SMR',
        'SN'=>'SEN',
        'SO'=>'SOM',
        'SR'=>'SUR',
        'ST'=>'STP',
        'SV'=>'SLV',
        'SY'=>'SYR',
        'SZ'=>'SWZ',
        'TC'=>'TCA',
        'TD'=>'TCD',
        'TF'=>'ATF',
        'TG'=>'TGO',
        'TH'=>'THA',
        'TJ'=>'TJK',
        'TK'=>'TKL',
        'TL'=>'TLS',
        'TM'=>'TKM',
        'TN'=>'TUN',
        'TO'=>'TON',
        'TR'=>'TUR',
        'TT'=>'TTO',
        'TV'=>'TUV',
        'TW'=>'TWN',
        'TZ'=>'TZA',
        'UA'=>'UKR',
        'UG'=>'UGA',
        'UM'=>'UMI',
        'US'=>'USA',
        'UY'=>'URY',
        'UZ'=>'UZB',
        'VA'=>'VAT',
        'VC'=>'VCT',
        'VE'=>'VEN',
        'VG'=>'VGB',
        'VI'=>'VIR',
        'VN'=>'VNM',
        'VU'=>'VUT',
        'WF'=>'WLF',
        'WS'=>'WSM',
        'YE'=>'YEM',
        'YT'=>'MYT',
        'ZA'=>'ZAF',
        'ZM'=>'ZMB',
        'ZW'=>'ZWE',
    );
}
