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

                $address_data = array();

                foreach ($details['results'][0]['address_components'] as $type) {
                    // parse Google Maps output into an array we can use
                    $address_data[$type['types'][0]] = $type['long_name'];
                }

                $lat = $details['results'][0]['geometry']['location']['lat'];
                $lng = $details['results'][0]['geometry']['location']['lng'];

                $address = $address_data['street_number'] . ' ' . $address_data['route'];

                if (empty($values['address'][$this->getPostIndex()])) {
                    $values['address'][$this->getPostIndex()] = $address;
                }
                if (empty($values['lat'][$this->getPostIndex()])) {
                    $values['lat'][$this->getPostIndex()] = str_replace(',','.', $lat);
                }
                if (empty($values['lng'][$this->getPostIndex()])) {
                    $values['lng'][$this->getPostIndex()] = str_replace(',','.', $lng);
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