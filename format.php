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
 * Section Carrousel course format — rendering entry point.
 *
 * Called by /course/view.php after authentication and capability checks.
 * $course, $PAGE, $displaysection and $marker are already set by the caller.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

// Retrieve format options and attach them to the $course object.
$format = course_get_format($course);
$course = $format->get_course();
$context = context_course::instance($course->id);

// Handle section marker (highlight) updates.
if (($marker >= 0) && has_capability('moodle/course:setcurrentsection', $context) && confirm_sesskey()) {
    $course->marker = $marker;
    if ($marker == 0) {
        \core_courseformat\formatactions::section($course->id)->remove_all_markers();
    } else {
        $sectioninfo = get_fast_modinfo($course->id)->get_section_info($marker);
        \core_courseformat\formatactions::section($course->id)->set_marker($sectioninfo, true);
    }
}

// Ensure the general section (section 0) always exists.
course_create_sections_if_missing($course, 0);

$renderer = $PAGE->get_renderer('format_sectioncarrousel');

if (!is_null($displaysection)) {
    $format->set_sectionnum($displaysection);
}

// Resolve the output class (allows subclassing via classes/output/courseformat/content.php).
$outputclass = $format->get_output_classname('content');
$widget = new $outputclass($format);
echo $renderer->render($widget);
