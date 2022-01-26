<?php

namespace Wpae\Pro\Filtering;

/**
 * Class FilteringCPT
 * @package Wpae\Pro\Filtering
 */
class FilteringCustom extends FilteringBase
{

    public static $variationWhere;
    public static $variationJoin = array();

    private $add_on;


    public function __construct()
    {
        parent::__construct();

        $this->add_on = \GF_Export_Add_On::get_instance();
    }

    /**
     * @return bool
     */
    public function parse()
    {

        if ($this->isFilteringAllowed()) {

            $this->checkNewStuff();

            // No Filtering Rules defined
            if (empty($this->filterRules)) return FALSE;

            $this->queryWhere = ($this->isExportNewStuff() || $this->isExportModifiedStuff()) ? $this->queryWhere . " AND (" : " AND (";

            // Apply Filtering Rules
            foreach ($this->filterRules as $rule) {
                if (is_null($rule->parent_id)) {
                    $this->parse_single_rule($rule);
                }
            }

            if ($this->meta_query || $this->tax_query) {
                $this->queryWhere .= " ) GROUP BY {$this->wpdb->posts}.ID";
            } else {
                $this->queryWhere .= ")";
            }
        }

    }


    /**
     *
     */
    public function getExcludeQueryWhere($postsToExclude)
    {
    }

    public function getModifiedQueryWhere($export)
    {
    }

    public function checkNewStuff()
    {
    }


    private function is_date_filter($element_label)
    {

        $gf_addon = \GF_Export_Add_On::get_instance()->add_on;

        $data_element = $gf_addon->get_data_element_by_slug($element_label);

        if(is_array($data_element)) {
            if (isset($data_element['filterable']) && $data_element['filterable'] === 'date') {
                return true;
            }
        }

        return false;

    }

    /**
     * @param $rule
     * @return mixed|void
     */
    public function parse_single_rule($rule)
    {

        apply_filters('wp_all_export_single_filter_rule', $rule);

        $gf_addon = \GF_Export_Add_On::get_instance()->add_on;

        $element_label = str_replace('rt_', '', $rule->element);
        $element_label = str_replace('cf_', '', $element_label);

        $data_element = $gf_addon->get_data_element_by_slug($element_label);

        if ($this->is_date_filter($element_label)) {
            $this->parse_date_field($rule);
            $rule->value = "'{$rule->value}'";
        }

        if (strpos($rule->element, 'cf_') === 0) {

            $table_alias = 'wp_gf_entry_meta';

            $meta_key = $data_element['element_meta_key'];

            $joinString = " INNER JOIN {$gf_addon->get_meta_table()} AS $table_alias ON ({$gf_addon->get_main_table()}.id = $table_alias.entry_id) ";

            $whereString = "$table_alias.meta_key = '$meta_key' AND $table_alias.meta_value " . $this->parse_condition($rule, false, $table_alias) . "";

            $this->queryJoin[] = $joinString;
            $this->queryWhere .= $whereString;

        } else if (strpos($rule->element, 'rt_') === 0) {

            $has_notes = [];
            $sql = "SELECT DISTINCT(entry_id) FROM {$gf_addon->get_related_table()} WHERE {$element_label} " . $this->parse_condition($rule);
            $results = $this->wpdb->get_results($sql);

            if ($results && is_array($results)) {
                foreach ($results as $result) {
                    $has_notes[] = $result->entry_id;
                }
            }

            if (is_array($has_notes) && !empty($has_notes)) {
                $query_string = "{$gf_addon->get_main_table()}.id IN ( " . implode(',', $has_notes) . " )";
                $this->queryWhere .= $query_string;
            } else {
                $this->queryWhere .= ' 1 = 1 ';
            }
        } else {
            $this->queryWhere .= $gf_addon->get_main_table() . "." . $rule->element . " " . $this->parse_condition($rule, true);

        }

        $this->recursion_parse_query($rule);
    }

    /**
     * @param $str
     * @param $prefix
     * @return string
     */
    private function removePrefix($str, $prefix)
    {
        if (substr($str, 0, strlen($prefix)) == $prefix) {
            $str = substr($str, strlen($prefix));
            return $str;
        }
        return $str;
    }
}
