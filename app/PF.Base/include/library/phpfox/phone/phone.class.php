<?php
/**
 * [PHPFOX_HEADER]
 */

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

defined('PHPFOX') or exit('NO DICE!');

/**
 * URL
 * Class is used to build the URL structure of the site.
 *
 * @copyright         [PHPFOX_COPYRIGHT]
 * @author            phpFox LLC
 * @package           Phpfox
 * @version           $Id: url.class.php 7062 2020-10-12 19:16:20Z Fern $
 */
class Phpfox_Phone
{
    private $rawPhone;

    /**
     * @var PhoneNumber
     */
    private $parsedPhone = null;

    /**
     * @var PhoneNumberUtil
     */
    private $oPhoneLib;

    /**
     * Phone number in national format
     */
    protected $phoneNational = null;

    /**
     * Phone number in international format
     */
    protected $phoneInternational = null;

    /**
     * Phone number in E164 format
     */
    protected $phoneE164 = null;

    /**
     * Phone number in RFC3966 format
     */
    protected $phoneRFC3966 = null;

    /**
     * @var null Phone country code
     */
    protected $phoneCountryCode = null;
    /**
     * @return mixed
     * @example "044 668 1800" in NATIONAL format
     */
    public function getPhoneNational()
    {
        if ($this->parsedPhone instanceof PhoneNumber && !$this->phoneNational) {
            $this->phoneNational = $this->oPhoneLib->format($this->parsedPhone, PhoneNumberFormat::NATIONAL);
        }
        return $this->phoneNational;
    }

    /**
     * @return mixed
     * @example "+41 44 668 1800" in INTERNATIONAL format
     */
    public function getPhoneInternational()
    {
        if ($this->parsedPhone instanceof PhoneNumber && !$this->phoneInternational) {
            $this->phoneInternational = $this->oPhoneLib->format($this->parsedPhone, PhoneNumberFormat::INTERNATIONAL);
        }
        return $this->phoneInternational;
    }

    /**
     * Check valid phone for searching
     * @param $sPhone
     * @param $bGetFullPhone
     * @return bool|mixed
     */
    public function checkValid($sPhone, $bGetFullPhone = false)
    {
        if (!$sPhone || !$this->setRawPhone($sPhone)) {
            return false;
        }
        return $bGetFullPhone ? $this->getPhoneE164() : $this->isValidPhone();
    }

    /**
     * @return mixed
     * E164 format is as per INTERNATIONAL format but with no formatting applied
     */
    public function getPhoneE164()
    {
        if ($this->parsedPhone instanceof PhoneNumber && !$this->phoneE164) {
            $this->phoneE164 = $this->oPhoneLib->format($this->parsedPhone, PhoneNumberFormat::E164);
        }
        return $this->phoneE164;
    }

    /**
     * @return mixed
     * RFC3966 is as per INTERNATIONAL format, but with all spaces and other
     * separating symbols replaced with a hyphen, and with any phone number extension appended with
     * ";ext=". It also will have a prefix of "tel:" added, e.g. "tel:+41-44-668-1800".
     */
    public function getPhoneRFC3966()
    {
        if ($this->parsedPhone instanceof PhoneNumber && !$this->phoneRFC3966) {
            $this->phoneRFC3966 = $this->oPhoneLib->format($this->parsedPhone, PhoneNumberFormat::RFC3966);
        }
        return $this->phoneRFC3966;
    }


    public function getCountryCode()
    {
        if ($this->parsedPhone instanceof PhoneNumber && !$this->phoneCountryCode) {
            $this->phoneCountryCode = $this->oPhoneLib->getRegionCodeForNumber($this->parsedPhone);
        }
        return $this->phoneCountryCode;
    }
    public function __construct()
    {
        $this->oPhoneLib = PhoneNumberUtil::getInstance();
    }

    /**
     * @return mixed
     */
    public function getRawPhone()
    {
        return $this->rawPhone;
    }

    /**
     * @param mixed $rawPhone
     */
    public function setRawPhone($rawPhone)
    {
        $this->rawPhone = $rawPhone;
        return $this->setParsedPhone();
    }

    /**
     * @return mixed
     */
    public function getParsedPhone()
    {
        return $this->parsedPhone;
    }

    private function setParsedPhone()
    {
        if ($this->parsedPhone === null && $this->rawPhone) {
            //Init phone
            try {
                $this->parsedPhone = $this->oPhoneLib->parse($this->rawPhone, strpos($this->rawPhone, '+') === 0 ? 'ZZ' : Phpfox_Request::instance()->getIpInfo(null, 'country_code'));
            } catch (NumberParseException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * Phone number is valid or not
     * @return bool
     */
    public function isValidPhone()
    {
        if ($this->parsedPhone) {
            return $this->oPhoneLib->isValidNumber($this->parsedPhone);
        }
        return false;
    }

    public function reset()
    {
        $this->rawPhone = null;
        $this->parsedPhone = null;
        $this->phoneE164 = null;
        $this->phoneInternational = null;
        $this->phoneNational = null;
        $this->phoneRFC3966 = null;
    }

    public function parsePhone($sPhone, $sFormat = 'international')
    {
        if (!empty($sPhone)) {
            $this->reset();
            $this->setRawPhone($sPhone);
            if ($this->isValidPhone()) {
                switch ($sFormat) {
                    case 'e164':
                        return $this->getPhoneE164();
                    case 'national':
                        return $this->getPhoneNational();
                    default:
                        return $this->getPhoneInternational();
                }
            }
        }
        return $sPhone;
    }
}