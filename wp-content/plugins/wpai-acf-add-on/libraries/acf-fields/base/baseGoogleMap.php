<?php

namespace wpai_acf_add_on\acf\fields;

/**
 * Class BaseGoogleMap
 * @package wpai_acf_add_on\acf\fields
 */
abstract class BaseGoogleMap extends Field {

    /**
     *  Retrieve address from google API
     */
    protected function getAddress(){

        // build search query
        $search = $this->getSearchUrl();

        // build api key
        $api_key = $this->getApiKey();

        if (!empty($search)) {

            // build $request_url for api call
            $request_url = 'https://maps.googleapis.com/maps/api/geocode/json?' . $search . $api_key;

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $request_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $json = curl_exec($curl);
            curl_close($curl);

            // parse api response
            if (!empty($json)) {

                $values = $this->getOption('values');
                $details = json_decode($json, TRUE);

                if (!empty($details['results'])) {
                    $address_data = array();
                    foreach ($details['results'][0]['address_components'] as $type) {
                        // parse Google Maps output into an array we can use
                        $address_data[$type['types'][0]] = $type;
                    }

                    $lat = $details['results'][0]['geometry']['location']['lat'];
                    $lng = $details['results'][0]['geometry']['location']['lng'];

                    $address = $address_data['street_number']['long_name'] . ' ' . $address_data['route']['long_name'];

                    if (empty($values['address'][$this->getPostIndex()])) {
                        $values['address'][$this->getPostIndex()] = $address;
                    }
                    if (empty($values['lat'][$this->getPostIndex()])) {
                        $values['lat'][$this->getPostIndex()] = str_replace(',','.', $lat);
                    }
                    if (empty($values['lng'][$this->getPostIndex()])) {
                        $values['lng'][$this->getPostIndex()] = str_replace(',','.', $lng);
                    }
                    if (empty($values['street_number'][$this->getPostIndex()]) && isset($address_data['street_number'])) {
                        $values['street_number'][$this->getPostIndex()] = $address_data['street_number']['long_name'];
                    }
                    if (empty($values['street_name'][$this->getPostIndex()]) && isset($address_data['route'])) {
                        $values['street_name'][$this->getPostIndex()] = $address_data['route']['long_name'];
                    }
                    if (empty($values['street_short_name'][$this->getPostIndex()]) && isset($address_data['route'])) {
                        $values['street_short_name'][$this->getPostIndex()] = $address_data['route']['short_name'];
                    }
                    if (empty($values['city'][$this->getPostIndex()]) && isset($address_data['locality'])) {
                        $values['city'][$this->getPostIndex()] = $address_data['locality']['long_name'];
                    }
                    if (empty($values['state'][$this->getPostIndex()]) && isset($address_data['administrative_area_level_1'])) {
                        $values['state'][$this->getPostIndex()] = $address_data['administrative_area_level_1']['long_name'];
                    }
                    if (empty($values['state_short'][$this->getPostIndex()]) && isset($address_data['administrative_area_level_1'])) {
                       $values['state_short'][$this->getPostIndex()] = $address_data['administrative_area_level_1']['short_name'];
                    }
                    if (empty($values['post_code'][$this->getPostIndex()]) && isset($address_data['postal_code'])) {
                       $values['post_code'][$this->getPostIndex()] = $address_data['postal_code']['long_name'];
                    }
                    if (empty($values['country'][$this->getPostIndex()]) && isset($address_data['country'])) {
                        $values['country'][$this->getPostIndex()] = $address_data['country']['long_name'];
                    }
                    if (empty($values['country_short'][$this->getPostIndex()]) && isset($address_data['country'])) {
                        $values['country_short'][$this->getPostIndex()] = $address_data['country']['short_name'];
                    }
                    if (empty($values['place_id'][$this->getPostIndex()]) && isset($details['results'][0]['place_id'])) {
                        $values['place_id'][$this->getPostIndex()] = $details['results'][0]['place_id'];
                    }
                }
                $this->setOption('values', $values);
            }
        }
    }

    /**
     * @return string
     */
    private function getSearchUrl(){
        $values = $this->getOption('values');
        $search = '';
        if (!empty($values['lat'][$this->getPostIndex()]) and !empty($values['lng'][$this->getPostIndex()])) {
            $search = 'latlng=' . rawurlencode($values['lat'][$this->getPostIndex()] . ',' . $values['lng'][$this->getPostIndex()]);
        }
        if (!empty($values['address'][$this->getPostIndex()]) and empty($values['lat'][$this->getPostIndex()]) and empty($values['lng'][$this->getPostIndex()])) {
            $search = 'address=' . rawurlencode($values['address'][$this->getPostIndex()]);
        }
        return $search;
    }

    /**
     * @return string
     */
    private function getApiKey(){
        $values = $this->getOption('values');
        $api_key = '';
        $parsed_data = $this->getParsedData();
        $xpath = $parsed_data['xpath'];
        if ($xpath['address_geocode'] == 'address_google_developers' && !empty($values['api_key'][$this->getPostIndex()])) {
            $api_key = '&key=' . $values['api_key'][$this->getPostIndex()];
        }
        elseif ($xpath['address_geocode'] == 'address_google_for_work' && !empty($values['client_id'][$this->getPostIndex()]) && !empty($values['signature'][$this->getPostIndex()])) {
            $api_key = '&client=' . $values['client_id'][$this->getPostIndex()] . '&signature=' . $values['signature'][$this->getPostIndex()];
        }
        return $api_key;
    }
}