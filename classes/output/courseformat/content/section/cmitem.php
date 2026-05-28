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

use renderer_base;
use stdClass;

/**
 * Activity item for the Section Carrousel format — adds flat icon fields for the card template.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmitem extends \core_courseformat\output\local\content\section\cmitem {

    /**
     * Point the renderer to our format-specific card template.
     *
     * The courseformat_named_templatable trait always returns core_courseformat/...
     * so we override it to load format_sectioncarrousel/local/content/section/cmitem.
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_sectioncarrousel/local/content/section/cmitem';
    }

    /**
     * Export data for the mustache card template.
     *
     * Extends the core export with flat `cardicon`, `cardiconclass`, and `cardiconpurpose`
     * so the template can render the icon without navigating deeply nested objects.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        $iconurl = $this->mod->get_icon_url();
        $data->cardicon        = $iconurl->out(false);
        $data->cardiconclass   = $iconurl->get_param('filtericon') ? '' : 'nofilter';
        $data->cardiconpurpose = plugin_supports('mod', $this->mod->modname, FEATURE_MOD_PURPOSE, MOD_PURPOSE_OTHER);

        // Custom card image: use uploaded file if one exists for this activity.
        $context = \context_module::instance($this->mod->id);
        $fs      = get_file_storage();
        $files   = $fs->get_area_files($context->id, 'format_sectioncarrousel', 'cardimage', 0, 'sortorder', false);
        foreach ($files as $file) {
            if ($file->get_filesize() > 0) {
                $data->cardimage = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    'format_sectioncarrousel',
                    'cardimage',
                    0,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false);
                break;
            }
        }

        return $data;
    }
}
