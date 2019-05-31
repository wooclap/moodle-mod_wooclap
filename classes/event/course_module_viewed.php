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

/**
 * Defines the view event.
 *
 * @package    mod_wooclap
 */

namespace mod_wooclap\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_wooclap course module viewed event class
 *
 * @package    mod_wooclap
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['objecttable'] = 'wooclap';
        parent::init();
    }
}
