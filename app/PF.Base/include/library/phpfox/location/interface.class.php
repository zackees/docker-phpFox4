<?php

/**
 * Location interface
 *
 * @copyright		[PHPFOX_COPYRIGHT]
 * @author			phpFox LLC
 * @package 		Phpfox
 * @version 		$Id: interface.class.php 2019-08-01 08:17:00Z phpFox LLC $
 */

interface Phpfox_Location_Interface
{

    /**
     * Get Lat, Long values from fulltext location
     * @param String $address
     */
    public function convertToLatLng($address);

    /**
     * Get fulltext location from lat, long
     * @param Float $latitude
     * @param Float $longitude
     */
    public function convertToAddress($latitude, $longitude);
}