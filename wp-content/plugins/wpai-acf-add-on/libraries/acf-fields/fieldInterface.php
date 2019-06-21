<?php

namespace wpai_acf_add_on\acf\fields;

/**
 * Interface FieldInterface
 * @package wpai_acf_add_on\acf\fields
 */
interface FieldInterface{

    /**
     * @param $xpath
     * @param $parsingData
     * @param array $args
     * @return mixed
     */
    public function parse($xpath, $parsingData, $args = array());

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import($importData, $args = array());

    /**
     * @param $importData
     * @return mixed
     */
    public function saved_post($importData);

    /**
     * @return mixed
     */
    public function view();

    /**
     *
     * Determines is field value empty or not
     *
     * @return mixed
     */
    public function isNotEmpty();

    /**
     * @return mixed
     */
    public function getOriginalFieldValueAsString();
}