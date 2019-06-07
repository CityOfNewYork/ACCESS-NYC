<?php

namespace wpai_acf_add_on\acf\groups;

require_once(__DIR__.'/group.php');

/**
 * Class GroupV5
 * @package wpai_acf_add_on\acf\groups
 */
class GroupV5 extends Group {

    /**
     *  Init field for ACF v5.x
     */
    public function initFields() {

        $acf_fields = get_posts(array(
            'posts_per_page' => -1,
            'post_type' => 'acf-field',
            'post_parent' => $this->group['ID'],
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        if (!empty($acf_fields)) {

            foreach ($acf_fields as $field) {

                $fieldData = empty($field->post_content) ? array() : unserialize($field->post_content);

                $fieldData['ID'] = $field->ID;
                $fieldData['id'] = $field->ID;
                $fieldData['label'] = $field->post_title;
                $fieldData['key'] = $field->post_name;
                if (empty($fieldData['name'])) {
                    $fieldData['name'] = $field->post_excerpt;
                }
                $this->fieldsData[] = $fieldData;
            }
        }
        // create field instances
        parent::initFields();
    }
}