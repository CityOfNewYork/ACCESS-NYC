<?php

namespace wpai_acf_add_on\acf\groups;

/**
 * Interface FieldInterface
 * @package wpai_acf_add_on\acf\groups
 */
interface GroupInterface{

    /**
     * @return mixed
     */
    public function initFields();

    /**
     * @return mixed
     */
    public function view();

    /**
     * @param $parsingData
     * @return mixed
     */
    public function parse($parsingData);

    /**
     * @param $importData
     * @return mixed
     */
    public function saved_post($importData);

}