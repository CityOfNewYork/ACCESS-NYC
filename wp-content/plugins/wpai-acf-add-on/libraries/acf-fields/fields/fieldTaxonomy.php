<?php

namespace wpai_acf_add_on\acf\fields;

use wpai_acf_add_on\acf\ACFService;

/**
 * Class FieldTaxonomy
 * @package wpai_acf_add_on\acf\fields
 */
class FieldTaxonomy extends Field {

    /**
     *  Field type key
     */
    public $type = 'taxonomy';

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

        $field = $this->getData('field');

        if ("yes" == $this->getOption('is_multiple_field')) {
            $multipleValue = $this->getOption('multiple_value');
            if (!is_array($multipleValue)) {
                $values = array_fill(0, $this->getOption('count'), $multipleValue);
            } else {
                $values = array();
                foreach ($multipleValue as $single_value) {
                    $values[] = array_fill(0, $this->getOption('count'), $single_value);
                }
                $this->setOption('is_multiple', TRUE);
            }
        } else {

            $values = array();
            if (!empty($xpath)) {
                if (!is_array($xpath)){
                    $xpath = array(
                        'value' => $xpath,
                        'delim' => ','
                    );
                }
                $this->setOption('is_multiple', 'nesting');
                $tx_name = $field['taxonomy'];
                $taxonomies_hierarchy = json_decode($xpath['value']);
                foreach ($taxonomies_hierarchy as $k => $taxonomy) {
                    if ("" == $taxonomy->xpath) {
                        continue;
                    }
                    $txes_raw = $this->getByXPath(str_replace('\'', '"', $taxonomy->xpath));
                    foreach ($txes_raw as $i => $tx_raw) {
                        if (empty($taxonomies_hierarchy[$k]->txn_names[$i])) {
                            $taxonomies_hierarchy[$k]->txn_names[$i] = array();
                        }
                        if (empty($values[$tx_name][$i])) {
                            $values[$tx_name][$i] = array();
                        }
                        $count_cats = count($values[$tx_name][$i]);

                        $delimetedTaxonomies = $this->getParent() ? array($tx_raw) : explode($xpath['delim'], $tx_raw);

                        if ('' != $tx_raw) {
                            foreach ($delimetedTaxonomies as $j => $cc) {
                                if ('' != $cc) {
                                    $terms = explode($xpath['delim'], $cc);
                                    if (!empty($terms)) {
                                        $terms = array_map('trim', $terms);
                                        foreach ($terms as $term) {
                                            $cat = get_term_by('name', trim($term), $tx_name) or $cat = get_term_by('slug', trim($term), $tx_name) or ctype_digit($term) and $cat = get_term_by('id', $term, $tx_name);
                                            if (!empty($taxonomy->parent_id)) {
                                                foreach ($taxonomies_hierarchy as $key => $value) {
                                                    if ($value->item_id == $taxonomy->parent_id and !empty($value->txn_names[$i])) {
                                                        foreach ($value->txn_names[$i] as $parent) {
                                                            $values[$tx_name][$i][] = array(
                                                                'name' => trim($term),
                                                                'parent' => $parent,
                                                                'assign' => 1
                                                                //$taxonomy->assign
                                                            );
                                                        }
                                                    }
                                                }
                                            } else {
                                                $values[$tx_name][$i][] = array(
                                                    'name' => trim($term),
                                                    'parent' => FALSE,
                                                    'assign' => 1
                                                    //$taxonomy->assign
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($count_cats < count($values[$tx_name][$i])) {
                            $taxonomies_hierarchy[$k]->txn_names[$i][] = $values[$tx_name][$i][count($values[$tx_name][$i]) - 1];
                        }
                    }
                }
            }
        }

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

        $parsedData = $this->getParsedData();

        $values = $this->getFieldValue();

        if ($parsedData['is_multiple'] !== TRUE and $parsedData['is_multiple'] == 'nesting') {

            if (!empty($values)) {

                foreach ($values as $tx_name => $txes) {

                    $assign_taxes = array();
                    // Create term if not exists.
                    foreach ($txes as $key => $single_tax) {
                        if (is_array($single_tax)) {
	                        $term_name = $single_tax['name'];
                            if (!empty($term_name)) {
	                            $parent_id = (!empty($single_tax['parent'])) ? pmxi_recursion_taxes($single_tax['parent'], $tx_name, $txes, $key) : '';

	                            $term = $parent_id ? is_exists_term($term_name, $tx_name, (int)$parent_id) : is_exists_term($term_name, $tx_name);

	                            if (empty($term) and !is_wp_error($term)) {
		                            $term_attr = array('parent' => (!empty($parent_id)) ? $parent_id : 0);
		                            $term = wp_insert_term(
			                            $term_name, // the term
			                            $tx_name, // the taxonomy
			                            $term_attr
		                            );
	                            }

	                            if (!empty($term) && !is_wp_error($term)) {
		                            $cat_id = is_array($term) ? $term['term_id'] : (int) $term;
		                            if ($cat_id and $single_tax['assign']) {
			                            if (!in_array($cat_id, $assign_taxes)) {
				                            $assign_taxes[] = $cat_id;
			                            }
		                            }
	                            }
                            }
                        }
                    }

                    if (!empty($assign_taxes)) {

                        $field = $this->getData('field');

                        $value = ($field['multiple'] || in_array($field['field_type'], array('multi_select', 'checkbox'))) ? $assign_taxes : array_shift($assign_taxes);

                        ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $value);
                    }
                }
            }
        }
        elseif ($parsedData['is_multiple']) {
            $mult_values = array();
            foreach ($values as $number => $value) {
                $mult_values[] = trim($value[$this->getPostIndex()]);
            }
            ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $mult_values);
        }
        else {
            $v = is_array($values) ? array_shift($values) : $values;
            ACFService::update_post_meta($this, $this->getPostID(), $this->getFieldName(), $v);
        }
    }

    /**
     * @param $importData
     */
    public function saved_post($importData) {

        $assign_taxes = ACFService::get_post_meta($this, $this->getPostID(), $this->getFieldName());

        $field = $this->getData('field');

        if (!empty($assign_taxes) && !empty($field['save_terms'])){
            if (!is_array($assign_taxes)){
                $assign_taxes = array($assign_taxes);
            }
            $assign_terms = array();
            foreach ($assign_taxes as $cat_id){
                $term = get_term_by('id', $cat_id, $field['taxonomy']);
                if (!is_wp_error($term) and !in_array($term->term_taxonomy_id, $assign_terms)) {
                    $assign_terms[] = $term->term_taxonomy_id;
                }
            }
            ACFService::associate_terms($this->getPostID(), (empty($assign_terms) ? FALSE : $assign_terms), $field['taxonomy'], $this->getLogger());
        }
    }

    /**
     * @return false|int|mixed|string
     */
    public function getFieldValue() {
        // Special case for nested taxonomies structure.
        if ($this->getOption('is_multiple') !== TRUE and $this->getOption('is_multiple') == 'nesting'){
            $parents = $this->getParents();
            $values = $this->options['values'];
            foreach ($values as $tx_name => $terms){
                $value = $terms[$this->getPostIndex()];
                if (!empty($parents)){
                    foreach ($value as $i => $term){
                        $termName = $term['name'];
                        foreach ($parents as $key => $parent) {
                            if (!empty($parent['delimiter'])) {
                                $termName = explode($parent['delimiter'], $termName);
                                $termName = $termName[$parent['index']];
                            }
                        }
                        $value[$i]['name'] = $termName;
                    }
                }
                $values[$tx_name] = $value;
            }
            return $values;
        } else {
			if ($this->getOption('is_multiple_field')) {
				$value = $this->options['values'];
			} else {
				$value = parent::getFieldValue();
			}

        }
        return $value;
    }

    /**
     * @return int
     */
    public function getCountValues() {
        $parents = $this->getParents();
        $count = 0;
        if (!empty($parents)){
            foreach ($this->getOption('values') as $tx_name => $tx_terms) {
                if (!empty($tx_terms[$this->getPostIndex()]) && is_array($tx_terms[$this->getPostIndex()])) {
                    foreach ($tx_terms[$this->getPostIndex()] as $tx_term) {
                        $value = $tx_term['name'];
                        $parentIndex = false;
                        foreach ($parents as $key => $parent) {
                            if ($parentIndex !== false){
                                $value = $value[$parentIndex];
                            }
                            $value = explode($parent['delimiter'], $value);
                            $parentIndex = $parent['index'];
                        }
                        if (count($value) > $count) {
                            $count = count($value);
                        }
                    }
                }
                if (!is_array($tx_terms) && !empty($tx_terms)) {
                    $count = 1;
                }
            }
        }
        return $count;
    }

    /**
     * @return bool
     */
    public function getOriginalFieldValueAsString() {
        return false;
    }
}