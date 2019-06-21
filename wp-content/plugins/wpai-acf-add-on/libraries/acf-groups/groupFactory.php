<?php

namespace wpai_acf_add_on\acf\groups;
use wpai_acf_add_on\acf\ACFService;

/**
 * Class GroupFactory
 * @package wpai_acf_add_on\acf\groups
 */
final class GroupFactory {

    /**
     * @param $groupData
     * @param $post
     * @return \wpai_acf_add_on\acf\groups\GroupV4|\wpai_acf_add_on\acf\groups\GroupV4Local|\wpai_acf_add_on\acf\groups\GroupV5|\wpai_acf_add_on\acf\groups\GroupV5Local
     */
    public static function create($groupData, $post = array()) {
        if (ACFService::isACFNewerThan('5.0.0')) {
            $group = is_numeric($groupData['ID']) ? new GroupV5($groupData, $post) : new GroupV5Local($groupData, $post);
        }
        else {
            $group = is_numeric($groupData['ID']) ? new GroupV4($groupData, $post) : new GroupV4Local($groupData, $post);
        }
        return $group;
    }

}