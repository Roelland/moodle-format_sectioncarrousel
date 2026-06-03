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

namespace format_sectioncarrousel;

/**
 * Event observer for the Section Carrousel course format.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Cleans up per-CM config entries when a course module is deleted.
     *
     * The plugin stores a per-activity subcourse image toggle as
     * config_plugins rows keyed by CM ID. Without this cleanup those rows
     * become orphans; in the unlikely event a CM ID is recycled they could
     * also corrupt the setting for the new activity.
     *
     * @param \core\event\course_module_deleted $event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        $cmid = $event->objectid;
        unset_config('cm' . $cmid . '_subcourseimage', 'format_sectioncarrousel');
    }
}
