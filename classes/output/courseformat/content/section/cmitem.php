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
        global $PAGE;
        if ($PAGE->user_is_editing()) {
            return 'core_courseformat/local/content/section/cmitem';
        }
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

        // Restriction overlay.
        // restrictionbadge       = show the lock icon at all.
        // restrictionlockstudent = true  → student is blocked: icon + title both open modal.
        //                          false → teacher: icon + title both navigate to the activity.
        $data->restrictionbadge       = false;
        $data->restrictionlockstudent = false;
        $data->restrictioninfo        = '';
        $data->activityurl            = '';

        if (!$this->mod->uservisible && !empty($this->mod->availableinfo)) {
            // Student can see the card but is blocked from accessing it.
            $data->restrictionbadge       = true;
            $data->restrictionlockstudent = true;
            $data->restrictioninfo        = $this->mod->availableinfo;
        } else if ($this->mod->uservisible && !empty($this->mod->availability)) {
            global $CFG;
            if (!empty($CFG->enableavailability)
                    && has_capability('moodle/course:viewhiddenactivities', $this->mod->context)) {
                $ci       = new \core_availability\info_module($this->mod);
                $fullinfo = $ci->get_full_information();
                if ($fullinfo) {
                    // Teacher: informational lock only — activity is still fully accessible.
                    $data->restrictionbadge       = true;
                    $data->restrictionlockstudent = false;
                    $data->restrictioninfo        = \core_availability\info::format_info(
                        $fullinfo, $this->mod->get_course()
                    );
                    $data->activityurl = $this->mod->url ? $this->mod->url->out(false) : '';
                }
            }
        }

        // Subcourse: resolve the referenced course once for both image and description modal.
        $data->subcoursemodal = false;
        if ($this->mod->modname === 'subcourse') {
            global $DB;
            $subcourse = $DB->get_record('subcourse', ['id' => $this->mod->instance], 'id,refcourse');
            if ($subcourse && !empty($subcourse->refcourse)) {
                $coursecontext = \context_course::instance($subcourse->refcourse, IGNORE_MISSING);

                // Course image (only when no custom image is uploaded and the toggle is on).
                $cmval = get_config('format_sectioncarrousel', 'cm' . $this->mod->id . '_subcourseimage');
                $usesubcourseimage = ($cmval === false)
                    ? (bool) get_config('format_sectioncarrousel', 'showsubcourseimage')
                    : (bool) $cmval;

                if (empty($data->cardimage) && $usesubcourseimage && $coursecontext) {
                    $overviewfiles = $fs->get_area_files(
                        $coursecontext->id, 'course', 'overviewfiles', 0, 'sortorder', false);
                    foreach ($overviewfiles as $file) {
                        if ($file->get_filesize() > 0) {
                            // itemid must be null: course/overviewfiles pluginfile handler
                            // does not include itemid in the URL path.
                            $data->cardimage = \moodle_url::make_pluginfile_url(
                                $file->get_contextid(),
                                'course',
                                'overviewfiles',
                                null,
                                $file->get_filepath(),
                                $file->get_filename()
                            )->out(false);
                            break;
                        }
                    }
                }

                // Description modal: show info button when the referenced course has a description.
                $refcourse = $DB->get_record('course', ['id' => $subcourse->refcourse],
                    'id,fullname,summary,summaryformat');
                if ($refcourse && trim($refcourse->summary) !== '') {
                    $modalcontext = $coursecontext ?: \context_system::instance();
                    $data->subcoursemodal      = true;
                    $data->subcoursemodaliid   = 'carrousel-modal-' . $this->mod->id;
                    $data->subcoursecoursename = format_string($refcourse->fullname);
                    $data->subcoursedescription = format_text(
                        $refcourse->summary,
                        $refcourse->summaryformat,
                        ['context' => $modalcontext]
                    );
                    $data->subcourseurl = (new \moodle_url('/course/view.php',
                        ['id' => $subcourse->refcourse]))->out(false);
                }
            }
        }

        return $data;
    }
}
