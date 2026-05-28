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
 * Main class for the Section Carrousel course format.
 *
 * @package   format_sectioncarrousel
 * @copyright 2026 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/lib.php');

use core\output\inplace_editable;

/**
 * Section Carrousel course format — shows each section as a carousel slide.
 */
class format_sectioncarrousel extends core_courseformat\base {

    /**
     * This format uses sections (one section = one carousel slide).
     */
    public function uses_sections(): bool {
        return true;
    }

    public function uses_course_index(): bool {
        return true;
    }

    public function uses_indentation(): bool {
        return false;
    }

    /**
     * Enable reactive (component-based) course editor.
     */
    public function supports_components(): bool {
        return true;
    }

    public function supports_ajax(): stdClass {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    public function supports_news(): bool {
        return true;
    }

    public function can_delete_section($section): bool {
        return true;
    }

    /**
     * Returns display name for a section (slide).
     */
    public function get_section_name($section): string {
        $section = $this->get_section($section);
        if ((string) $section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        }
        return $this->get_default_section_name($section);
    }

    public function get_default_section_name($section): string {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_sectioncarrousel');
        }
        return get_string('newsection', 'format_sectioncarrousel');
    }

    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * Returns the view URL for a section.
     */
    public function get_view_url($section, $options = []): moodle_url {
        $course  = $this->get_course();
        $section = (is_null($section) || $section instanceof section_info)
            ? $section
            : $this->get_section($section, IGNORE_MISSING);

        if (array_key_exists('sr', $options)) {
            $pagesection = !is_null($options['sr'])
                ? $this->get_section($options['sr'], IGNORE_MISSING)
                : null;
        } else if ($options['navigation'] ?? false) {
            $pagesection = ($section && $section->get_component_instance())
                ? $section->get_component_instance()->get_parent_section()
                : $section;
        } else {
            $pagesection = null;
        }

        if (is_null($pagesection)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        } else {
            $url = new moodle_url('/course/section.php', ['id' => $pagesection->id]);
        }

        if ($this->uses_sections() && $section && ($section->id != $pagesection?->id)) {
            $url->set_anchor('section-' . $section->section);
        }

        return $url;
    }

    /**
     * Default empty blocks layout.
     */
    public function get_default_blocks(): array {
        return [
            BLOCK_POS_LEFT  => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Course-level format options.
     * hiddensections — controls visibility of hidden slides.
     */
    public function course_format_options($foreditform = false): array {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'hiddensections' => [
                    'default' => $courseconfig->hiddensections,
                    'type'    => PARAM_INT,
                ],
            ];
        }

        if ($foreditform && !isset($courseformatoptions['hiddensections']['label'])) {
            $hiddensectionslist = new core\output\choicelist();
            $hiddensectionslist->set_allow_empty(false);
            $hiddensectionslist->add_option(1, new lang_string('hiddensectionsinvisible'),
                ['description' => new lang_string('hiddensectionsinvisible_description')]);
            $hiddensectionslist->add_option(0, new lang_string('hiddensectionscollapsed'),
                ['description' => new lang_string('hiddensectionscollapsed_description')]);

            $courseformatoptions = array_merge_recursive($courseformatoptions, [
                'hiddensections' => [
                    'label'            => new lang_string('hiddensections'),
                    'element_type'     => 'choicedropdown',
                    'element_attributes' => [$hiddensectionslist],
                ],
            ]);
        }

        return $courseformatoptions;
    }

    public function update_course_format_options($data, $oldcourse = null): bool {
        $data = (array) $data;
        if ($oldcourse !== null) {
            $oldcourse = (array) $oldcourse;
            foreach ($this->course_format_options() as $key => $unused) {
                if (!array_key_exists($key, $data) && array_key_exists($key, $oldcourse)) {
                    $data[$key] = $oldcourse[$key];
                }
            }
        }
        return $this->update_format_options($data);
    }

    public function allow_stealth_module_visibility($cm, $section): bool {
        return !$section->section || $section->visible;
    }

    /**
     * Handles show/hide section actions.
     */
    public function section_action($section, $action, $sr): ?array {
        global $PAGE;

        $rv       = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_sectioncarrousel');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }

        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);
        $rv['section_availability'] = $renderer->render($availability);

        return $rv;
    }

    public function get_config_for_external(): array {
        return $this->get_format_options();
    }
}

/**
 * Returns filemanager options for the activity card image field.
 */
function format_sectioncarrousel_cardimage_filemanageroptions(): array {
    global $COURSE;
    return [
        'maxbytes'      => $COURSE->maxbytes,
        'subdirs'       => 0,
        'accepted_types' => 'image',
        'maxfiles'      => 1,
    ];
}

/**
 * Adds the card image filemanager to the activity settings form.
 */
function format_sectioncarrousel_coursemodule_standard_elements($formwrapper, $form): void {
    $form->addElement('header', 'sectioncarrouselhdr',
        get_string('cardimagesection', 'format_sectioncarrousel'));

    $form->addElement('filemanager', 'cardimage_filemanager',
        get_string('cardimage', 'format_sectioncarrousel'), '',
        format_sectioncarrousel_cardimage_filemanageroptions());

    $context = $formwrapper->get_context();
    $values  = new stdClass();
    $values  = file_prepare_standard_filemanager(
        $values,
        'cardimage',
        format_sectioncarrousel_cardimage_filemanageroptions(),
        $context,
        'format_sectioncarrousel',
        'cardimage',
        0
    );
    $form->setDefaults((array) $values);
}

/**
 * Saves the uploaded card image after the activity settings form is submitted.
 */
function format_sectioncarrousel_coursemodule_edit_post_actions($data, $course) {
    $context = context_module::instance($data->coursemodule);
    file_postupdate_standard_filemanager(
        $data,
        'cardimage',
        format_sectioncarrousel_cardimage_filemanageroptions(),
        $context,
        'format_sectioncarrousel',
        'cardimage',
        0
    );
    return $data;
}

/**
 * Serves the uploaded card image files.
 */
function format_sectioncarrousel_pluginfile($course, $cm, context $context,
        $filearea, $args, $forcedownload, array $options = []): bool {
    if ($filearea !== 'cardimage') {
        return false;
    }
    $itemid       = array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath     = "/{$context->id}/format_sectioncarrousel/{$filearea}/{$itemid}/{$relativepath}";
    $fs           = get_file_storage();
    $file         = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}

/**
 * Implements in-place section name editing.
 */
function format_sectioncarrousel_inplace_editable(string $itemtype, int $itemid, $newvalue): inplace_editable {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');

    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id
              WHERE s.id = ? AND c.format = ?',
            [$itemid, 'sectioncarrousel'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name(
            $section, $itemtype, $newvalue
        );
    }
}
