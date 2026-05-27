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

namespace format_sectioncarrousel\output\courseformat\content\section;

/**
 * Section activity list for the Section Carrousel format.
 *
 * Only exists to redirect the template to our format's own cmlist.mustache,
 * which wraps activities in a flex-wrap card grid instead of a plain list.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmlist extends \core_courseformat\output\local\content\section\cmlist {

    /**
     * Point the renderer at our format-specific flex-wrap container template.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_sectioncarrousel/local/content/section/cmlist';
    }

    /**
     * Extend the parent data with carousel grouping.
     *
     * When a section has 4 or more activities the template switches to a
     * Bootstrap 5 multi-item carousel; otherwise the plain flex-wrap grid is used.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $data = parent::export_for_template($output);

        $cms = $data->cms ?? [];

        if (count($cms) >= 4) {
            $data->usecarousel = true;
            $data->carouselid  = 'carrousel-cms-' . uniqid();

            $slides = [];
            foreach (array_chunk($cms, 3) as $i => $chunk) {
                $slides[] = [
                    'active' => ($i === 0),
                    'items'  => array_values($chunk),
                ];
            }
            $data->slides = $slides;
        } else {
            $data->usecarousel = false;
            $data->slides      = [];
        }

        return $data;
    }
}
