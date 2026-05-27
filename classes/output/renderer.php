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

namespace format_sectioncarrousel\output;

use core_courseformat\output\section_renderer;
use moodle_page;

/**
 * Renderer for the Section Carrousel course format.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {

    /**
     * @param moodle_page $page
     * @param string $target one of the rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Allow users with only the highlight capability to toggle editing mode.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Section title wrapped in an in-place editable link.
     *
     * @param \section_info|\stdClass $section
     * @param \stdClass $course
     * @return string HTML
     */
    public function section_title($section, $course): string {
        return $this->render(
            course_get_format($course)->inplace_editable_render_section_name($section)
        );
    }

    /**
     * Section title without a navigable link (used on single-section pages).
     *
     * @param \section_info|\stdClass $section
     * @param int|\stdClass $course
     * @return string HTML
     */
    public function section_title_without_link($section, $course): string {
        return $this->render(
            course_get_format($course)->inplace_editable_render_section_name($section, false)
        );
    }

    /**
     * Render the full course content using our format-specific content template.
     *
     * section_renderer::render() strips the class name to "content" and calls this
     * method, so we can bypass courseformat_named_templatable and use our own template.
     *
     * @param \core_courseformat\output\local\content $widget
     * @return string HTML
     */
    public function render_content(\core_courseformat\output\local\content $widget): string {
        $data = $widget->export_for_template($this);
        return $this->render_from_template('format_sectioncarrousel/local/content', $data);
    }
}
