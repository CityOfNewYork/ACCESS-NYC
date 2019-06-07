<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldUser
 * @package wpai_acf_add_on\acf\fields
 */
class FieldUser extends Field {

    /**
     *  Field type key
     */
    public $type = 'user';

    /**
     *
     * Parse field data
     *
     * @param $xpath
     * @param $parsingData
     * @param array $args
     */
    public function parse($xpath, $parsingData, $args = array()) {
        parent::parse($xpath, $parsingData, $args);
        $values = $this->getByXPath($xpath);
        $this->setOption('values', $values);
    }

    /**
     * @param $importData
     * @param array $args
     * @return mixed
     */
    public function import($importData, $args = array()) {
        $isUpdated = parent::import($importData, $args);
        if (!$isUpdated){
            return FALSE;
        }
        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $this->getFieldValue());
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {
        if (strpos(parent::getFieldValue(), ",")) {
            $users = array_map('trim', explode(",", parent::getFieldValue()));
            if (!empty($users)):
                foreach ($users as $key => $author) {
                    $user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
                    $users[$key] = empty($user) ? "" : $user->ID;
                }
            endif;
            return $users;
        }
        else {
            $author = parent::getFieldValue();
            $user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
            return empty($user) ? "" : $user->ID;
        }
    }
}