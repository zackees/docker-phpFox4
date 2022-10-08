<?php
/**
 * Gmap class https://cloud.google.com/maps-platform/
 */

class Phpfox_Location_Gmap implements Phpfox_Location_Interface
{

    private $apiKey;

    private $baseApiUrl;

    public function __construct()
    {
        $this->apiKey = Phpfox::getParam('core.google_api_key');
        $this->baseApiUrl = 'https://maps.googleapis.com/maps/api/geocode/json?';
    }

    public function convertToLatLng($address)
    {
        if ($this->apiKey) {
            $response = Phpfox::getLib('request')->send($this->getRequestUrl(['address' => urlencode($address)]), [], 'GET', $_SERVER['HTTP_USER_AGENT']);
            $response = json_decode($response, true);
            $results = isset($response['results']) ? reset($response['results']) : [];
            if (is_array($results) && count($results) && isset($response['status']) && $response['status'] == 'OK') {
                $geometryLocation = $results['geometry']['location'];
                return [
                    'latitude' =>  $geometryLocation['lat'],
                    'longitude' => $geometryLocation['lng'],
                    'gmap_address' => isset($results['formatted_address']) ? $results['formatted_address'] : ''
                ];
            }
        }
        return false;
    }

    public function convertToAddress($latitude, $longitude)
    {
        if ($this->apiKey) {
            $response = Phpfox::getLib('request')->send($this->getRequestUrl(['latlng' => $latitude.','.$longitude]), [], 'GET', $_SERVER['HTTP_USER_AGENT']);
            $response = json_decode($response, true);
            $results = isset($response['results']) ? reset($response['results']) : [];
            if (count($results) && isset($response['status']) && $response['status'] == 'OK') {
                return isset($results['formatted_address']) ? $results['formatted_address'] : '';
            }
        }
        return false;
    }

    protected function getRequestUrl($params = [])
    {
        $params['key'] = $this->apiKey;
        $stringParams = http_build_query($params);

        return $this->baseApiUrl . $stringParams;
    }
}