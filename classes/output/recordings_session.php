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

namespace mod_bigbluebuttonbn\output;

use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\logger;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderer for recording section.
 *
 * @package   mod_bigbluebuttonbn
 * @copyright 2010 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David  (laurent.david [at] call-learning [dt] fr)
 */
class recordings_session implements renderable, templatable {

    /**
     * @var instance
     */
    protected $instance;

    /**
     * recording_section constructor.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Export for template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $isrecordedtype = $this->instance->is_type_room_and_recordings() || $this->instance->is_type_recordings_only();
        $context = \context_course::instance($this->instance->get_course_id());

        $participantlist = roles::get_users_array($context, $this->instance->get_instance_data());
        $context = (object) [
            'bbbid' => $this->instance->get_instance_id(),
            'groupid' => $this->instance->get_group_id(),
            'has_recordings' => $this->instance->is_recorded() && $isrecordedtype,
            'has_users' => $participantlist, // ELO
            'sessions' => $this->get_sessions(),
            'searchbutton' => [
                'value' => '',
            ],
        ];

        if ((bool) config::get('importrecordings_enabled') && $this->instance->can_import_recordings()) {
            $button = new \single_button(
                $this->instance->get_import_url(),
                get_string('view_recording_button_import', 'mod_bigbluebuttonbn')
            );
            $context->import_button = $button->export_for_template($output);
        }

        return $context;
    }

    public function get_sessions() {
        global $DB;
        $select = "courseid = ? AND log = ?";
        $params = [
            'courseid' => $this->instance->get_course_id(),
            'log' => logger::EVENT_START,
        ];
        $sessions = $DB->get_records_select('bigbluebuttonbn_logs', $select, $params);
        return $sessions;
    }
}
