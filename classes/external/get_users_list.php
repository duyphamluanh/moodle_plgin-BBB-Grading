<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_bigbluebuttonbn\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_course;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\helpers\roles;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * External service to fetch meeting information.
 *
 * @package   mod_bigbluebuttonbn
 * @category  external
 * @copyright 2018 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_users_list extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bigbluebuttonbnid' => new external_value(PARAM_INT, 'bigbluebuttonbn instance id'),
        ]);
    }

    /**
     * Fetch meeting information.
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @return array
     */
    public static function execute(int $bigbluebuttonbnid): array {
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $context = \context_course::instance($instance->get_course_id());
        $participantlist = self::get_users_array($context, $instance->get_instance_data());
        return array('users' => $participantlist);
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'users' => new external_multiple_structure(new external_single_structure([
               'id' => new external_value(PARAM_INT, 'User id'),
               'name' => new external_value(PARAM_TEXT, 'User name'),
               'mail' => new external_value(PARAM_TEXT, 'User mail')
            ]))
        ]);
    }

    /**
     * Returns an array containing all the users in a context wrapped for html select element.
     *
     * @param context_course $context
     * @param null $bbactivity
     * @return array $users
     */
    public static function get_users_array(context_course $context, $bbactivity = null) {
        // CONTRIB-7972, check the group of current user and course group mode.
        $groups = null;
        $users = (array) get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);
        $course = get_course($context->instanceid);
        $groupmode = groups_get_course_groupmode($course);
        if ($bbactivity) {
            list($bbcourse, $cm) = get_course_and_cm_from_instance($bbactivity->id, 'bigbluebuttonbn');
            $groupmode = groups_get_activity_groupmode($cm);

        }
        if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            global $USER;
            $groups = groups_get_all_groups($course->id, $USER->id);
            $users = [];
            foreach ($groups as $g) {
                $users += (array) get_enrolled_users($context, '', $g->id, 'u.*', null, 0, 0, true);
            }
        }
        return array_map(
        function($u) {
            return ['id' => $u->id, 'name' => fullname($u), 'mail' => $u->email];
        },
        $users);
    }
}


