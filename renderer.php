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
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage tabbedtopcoll
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/topcoll/renderer.php');
require_once($CFG->dirroot . '/course/format/tabbedtopcoll/lib.php');

class format_tabbedtopcoll_renderer extends format_topcoll_renderer {


    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $CFG, $DB, $PAGE;

        $tabs = array();

//        $this->page->requires->js_call_amd('format_tabbedtopcoll/tabs', 'init', array());
        $this->require_js();

        $modinfo = get_fast_modinfo($course);
        $course = $this->courseformat->get_course();
        if (empty($this->tcsettings)) {
            $this->tcsettings = $this->courseformat->get_settings();
        }
        $options = $DB->get_records('course_format_options', array('courseid' => $course->id));
        $format_options=array();
        foreach($options as $option) {
            $format_options[$option->name] =$option->value;
        }

        $context = context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now on to the main stage..
        $numsections = course_get_format($course)->get_last_section_number();
        $sections = $modinfo->get_section_info_all();

        // add an invisible div that carries the course ID to be used by JS
        // add class 'single_section_tabs' when option is set so JS can play accordingly
        $class = ($format_options['single_section_tabs'] ? 'single_section_tabs' : '');
        echo html_writer::start_tag('div', array('id' => 'courseid', 'courseid' => $course->id, 'class' => $class));
        echo html_writer::end_tag('div');

        // display section-0 on top of tabs if option is checked
        echo $this->render_section0_ontop($course, $sections, $format_options, $modinfo);

        // the tab navigation
        $tabs = $this->prepare_tabs($course, $format_options, $sections);

        // rendering the tab navigation
        echo $this->render_tabs($format_options);

        // Now the list of sections..
        if ($this->formatresponsive) {
            $this->tccolumnwidth = 100; // Reset to default.
        }
        echo $this->start_section_list();

        // Render the sections
        echo $this->render_sections($course, $sections, $format_options, $modinfo, $context);
    }

    // Require the jQuery file for this class
    public function require_js() {
        $this->page->requires->js_call_amd('format_tabbedtopcoll/tabs', 'init', array());
    }

    public function check_section_ids($courseid, $sections, $section_ids, $section_nums, $tab_sections, $tab_section_nums, $i) {
        global $DB;
        // check section IDs are valid for this course - and repair them using section numbers if they are not
        $tab_format_record = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i));
        $ids_have_changed = false;
        $new_section_nums = array();
        foreach($section_ids as $index => $section_id) {
            $section = $sections[$section_id];
            $new_section_nums[] = $section->section;
            if($section_id && !($section)) {
                $section = $DB->get_record('course_sections', array('course' => $courseid, 'section' => $section_nums[$index]));
                $tab_sections = str_replace($section_id, $section->id, $tab_sections);
                $ids_have_changed = true;
            }
        }
        if($ids_have_changed) {
            $DB->update_record('course_format_options', array('id' => $tab_format_record->id, 'value' => $tab_sections));
        }
        else { // all IDs are good - so check stored section numbers and restore them with the real numbers in case they have changed
            $new_sectionnums = implode(',', $new_section_nums);
            if($tab_section_nums !== $new_sectionnums) { // the stored section numbers seems to be different
                if($DB->record_exists('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i.'_sectionnums'))) {
                    $tab_format_record = $DB->get_record('course_format_options', array('courseid' => $courseid, 'name' => 'tab'.$i.'_sectionnums'));
                    $DB->update_record('course_format_options', array('id' => $tab_format_record->id, 'value' => $new_sectionnums));
                } else {
                    $new_tab_format_record = new \stdClass();
                    $new_tab_format_record->courseid = $courseid;
                    $new_tab_format_record->format = 'tabbedtopcoll';
                    $new_tab_format_record->sectionid = 0;
                    $new_tab_format_record->name = 'tab'.$i.'_sectionnums';
                    $new_tab_format_record->value = $new_sectionnums;
                    $DB->insert_record('course_format_options', $new_tab_format_record);
                }
            }
        }
        return $tab_sections;
    }

    // Prepare the tabs for rendering
    public function prepare_tabs($course, $format_options, $sections) {
        global $CFG, $DB, $PAGE;

        // prepare a maximum of 10 user tabs (0..9)
        $max_tabs = 9;
        $tabs = array();

        // preparing the tabs
        $count_tabs = 0;
        for ($i = 0; $i <= $max_tabs; $i++) {
            $tab_sections = '';
            $tab_section_nums = '';

            // check section IDs and section numbers for tabs other than tab0
            if($i > 0) {
                $tab_sections = str_replace(' ', '', $format_options['tab' . $i]);
                $tab_section_nums = str_replace(' ', '', $format_options['tab' . $i. '_sectionnums']);
                $section_ids = explode(',', $tab_sections);
                $section_nums = explode(',', $tab_section_nums);
                $tab_sections = $this->check_section_ids($course->id, $sections, $section_ids, $section_nums, $tab_sections, $tab_section_nums,$i);
            }

            $tab = new stdClass();
            $tab->id = "tab" . $i;
            $tab->name = "tab" . $i;
            $tab->title = $format_options['tab' . $i . '_title'];
            $tab->generic_title = ($i === 0 ? get_string('tab0_generic_name', 'format_tabbedtopcoll'):'Tab '.$i);
            $tab->sections = $tab_sections;
            $tab->section_nums = $tab_section_nums;
            $tabs[$tab->id] = $tab;
        }
        $this->tabs = $tabs;
        return $tabs;
    }

    // Render the tabs in sequence order if present or ascending otherwise
    public function render_tabs($format_options) {
        $o = html_writer::start_tag('ul', array('class'=>'tabs nav nav-tabs row'));

        $tab_seq = array();
        if ($format_options['tab_seq']) {
            $tab_seq = explode(',',$format_options['tab_seq']);
        }

        // if a tab sequence is equal to the number of tabs is found use it to arrange the tabs otherwise show them in default order
        if(sizeof($tab_seq) == sizeof($this->tabs)) {
            foreach ($tab_seq as $tabid) {
                $tab = $this->tabs[$tabid];
                $o .= $this->render_tab($tab);
            }
        } else {
            foreach ($this->tabs as $tab) {
                $o .= $this->render_tab($tab);
            }
        }
        $o .= html_writer::end_tag('ul');

        return $o;
    }

    // Render a standard tab
    public function render_tab($tab) {
        global $DB, $PAGE, $OUTPUT;
        $o = '';
        if($tab->sections == '') {
            $o .= html_writer::start_tag('li', array('class'=>'tabitem nav-item', 'style' => 'display:none;'));
        } else {
            $o .= html_writer::start_tag('li', array('class'=>'tabitem nav-item'));
        }

        $sections_array = explode(',', str_replace(' ', '', $tab->sections));
        if($sections_array[0]) {
            while ($sections_array[0] == "0") { // remove any occurences of section-0
                array_shift($sections_array);
            }
        }

        if($PAGE->user_is_editing()) {
            // get the format option record for the given tab - we need the id
            // if the record does not exist, create it first
            if(!$DB->record_exists('course_format_options', array('courseid' => $PAGE->course->id, 'name' => $tab->id.'_title'))) {
                $record = new stdClass();
                $record->courseid = $PAGE->course->id;
                $record->format = 'tabbedtopcoll';
                $record->section = 0;
                $record->name = $tab->id.'_title';
                $record->value = ($tab->id == 'tab0' ? get_string('tabzero_title', 'format_tabbedtopcoll') :'Tab '.substr($tab->id,3));
                $DB->insert_record('course_format_options', $record);
            }

            $format_option_tab = $DB->get_record('course_format_options', array('courseid' => $PAGE->course->id, 'name' => $tab->id.'_title'));
            $itemid = $format_option_tab->id;
        } else {
            $itemid = false;
        }

        if ($tab->id == 'tab0') {
            $o .= '<span 
                data-toggle="tab" id="'.$tab->id.'" 
                sections="'.$tab->sections.'" 
                section_nums="'.$tab->section_nums.'" 
                class="tablink nav-link " 
                tab_title="'.$tab->title.'", 
                generic_title = "'.$tab->generic_title.'"
                >';
        } else {
            $o .= '<span 
                data-toggle="tab" id="'.$tab->id.'" 
                sections="'.$tab->sections.'" 
                section_nums="'.$tab->section_nums.'" 
                class="tablink topictab nav-link " 
                tab_title="'.$tab->title.'" 
                generic_title = "'.$tab->generic_title.'" 
                style="'.($PAGE->user_is_editing() ? 'cursor: move;' : '').'">';
        }
        // render the tab name as inplace_editable
        $tmpl = new \core\output\inplace_editable('format_tabbedtopcoll', 'tabname', $itemid,
            $PAGE->user_is_editing(),
            format_string($tab->title), $tab->title, get_string('tabtitle_edithint', 'format_tabbedtopcoll'),  get_string('tabtitle_editlabel', 'format_tabbedtopcoll', format_string($tab->title)));
        $o .= $OUTPUT->render($tmpl);
        $o .= "</span>";
        $o .= html_writer::end_tag('li');
        return $o;
    }

    /**
     * Generate the starting container html for a list of sections.
     * @return string HTML to output.
     */
    protected function start_section_list() {
        if ($this->bsnewgrid) {
            return html_writer::start_tag('ul', array('class' => 'topics bsnewgrid'));
        } else {
            return html_writer::start_tag('ul', array('class' => 'topics'));
        }
    }

    // A slightly different header for section-0 when showing on top
    protected function section0_ontop_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE;

        $o = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o.= html_writer::start_tag('li', array('id' => 'section-'.$section->section, 'section-id' => $section->id,
            'class' => 'section ontop main clearfix'.$sectionstyle, 'role'=>'region',
            'aria-label'=> get_section_name($course, $section)));

        // Create a span that contains the section title to be used to create the keyboard section move menu.
        $o .= html_writer::tag('span', get_section_name($course, $section), array('class' => 'hidden sectionname'));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        $sectionname = html_writer::tag('span', $this->section_title($section, $course));
        $o.= $this->output->heading($sectionname, 3, 'sectionname' . $classes);

//        $o .= $this->section_availability($section);

//        $o .= html_writer::start_tag('div', array('class' => 'summary'));
        if ($section->uservisible || $section->visible) {
            // Show summary if section is available or has availability restriction information.
            // Do not show summary if section is hidden but we still display it because of course setting
            // "Hidden sections are shown in collapsed form".
            $o .= $this->format_summary_text($section);
        }
//        $o .= html_writer::end_tag('div');

        return $o;
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @param int $sectionreturn The section to return to after an action.
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn = null) {
        $o = '';

        $sectionstyle = '';
        $rightcurrent = '';
        $context = context_course::instance($course->id);

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if ($section->section == $this->currentsection) {
                $sectionstyle = ' current';
                $rightcurrent = ' left';
            }
        }

        if ((!$this->formatresponsive) && ($section->section != 0) &&
            ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $sectionstyle .= ' ' . $this->get_column_class($this->tcsettings['layoutcolumns']);
        }
        $liattributes = array(
            'id' => 'section-' . $section->section,
            'section-id' => $section->id,
            'class' => 'section main clearfix' . $sectionstyle,
            'role' => 'region',
            'aria-label' => $this->courseformat->get_topcoll_section_name($course, $section, false)
        );
        if (($this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 2)) { // Horizontal column layout.
            $liattributes['style'] = 'width: ' . $this->tccolumnwidth . '%;';
        }
        $o .= html_writer::start_tag('li', $liattributes);

        if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $rightcontent = '';
            if (($section->section != 0) && $this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));

                // Do not show a cog wheel for edit purposes as there is an edit menu
//                $rightcontent .= html_writer::link($url,
//                    $this->output->pix_icon('t/edit', get_string('edit')),
//                    array('title' => get_string('editsection', 'format_tabbedtopcoll'), 'class' => 'tceditsection'));
            }
            $rightcontent .= $this->section_right_content($section, $course, $onsectionpage);

            if ($this->rtl) {
                // Swap content.
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
            } else {
                $o .= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
                $o .= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
            }
        }
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        if (($onsectionpage == false) && ($section->section != 0)) {
            $o .= html_writer::start_tag('div',
                array('class' => 'sectionhead toggle toggle-'.$this->tcsettings['toggleiconset'],
                    'id' => 'toggle-'.$section->section)
            );

            if ((!($section->toggle === null)) && ($section->toggle == true)) {
                $toggleclass = 'toggle_open';
                $ariapressed = 'true';
                $sectionclass = ' sectionopen';
            } else {
                $toggleclass = 'toggle_closed';
                $ariapressed = 'false';
                $sectionclass = '';
            }
            $toggleclass .= ' the_toggle ' . $this->tctoggleiconsize;
            $o .= html_writer::start_tag('span',
                array('class' => $toggleclass, 'role' => 'button', 'aria-pressed' => $ariapressed)
            );

            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }

            if ($this->userisediting) {
                $title = $this->section_title($section, $course);
            } else {
                $title = $this->courseformat->get_topcoll_section_name($course, $section, true);
            }
            if ((($this->mobiletheme === false) && ($this->tablettheme === false)) || ($this->userisediting)) {
                $o .= $this->output->heading($title, 3, 'sectionname');
            } else {
                $o .= html_writer::tag('h3', $title); // Moodle H3's look bad on mobile / tablet with CT so use plain.
            }

            $o .= $this->section_availability($section);

            $o .= html_writer::end_tag('span');
            $o .= html_writer::end_tag('div');

            if ($this->tcsettings['showsectionsummary'] == 2) {
                $o .= $this->section_summary_container($section);
            }

            $o .= html_writer::start_tag('div',
                array('class' => 'sectionbody toggledsection' . $sectionclass,
                    'id' => 'toggledsection-' . $section->section)
            );

            if ($this->userisediting) {
                // CONTRIB-7434.
                $o .= html_writer::tag('span',
                    $this->courseformat->get_topcoll_section_name($course, $section, false),
                    array('class' => 'hidden', 'aria-hidden' => 'true'));
            }

            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                // again no cog wheel as edit now is part of the drop down menu
//                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
//                $o .= html_writer::link($url,
//                    $this->output->pix_icon('t/edit', get_string('edit')),
//                    array('title' => get_string('editsection', 'format_tabbedtopcoll'))
//                );
            }

            if ($this->tcsettings['showsectionsummary'] == 1) {
                $o .= $this->section_summary_container($section);
            }
        } else {
            // When on a section page, we only display the general section title, if title is not the default one.
            $hasnamesecpg = ($section->section == 0 && (string) $section->name !== '');

            if ($hasnamesecpg) {
                $o .= $this->output->heading($this->section_title($section, $course), 3, 'section-title');
            }
            $o .= $this->section_availability($section);
            $o .= html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            if ($this->userisediting && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= html_writer::link($url,
                    $this->output->pix_icon('t/edit', get_string('edit')),
                    array('title' => get_string('editsection', 'format_tabbedtopcoll'))
                );
            }
            $o .= html_writer::end_tag('div');
        }
        return $o;
    }

    // display section-0 on top of tabs if option is checked
    public function render_section0_ontop($course, $sections, $format_options, $modinfo) {
        global $PAGE;
        $o = '';
        if($format_options['section0_ontop']) {
            $section0 = $sections[0];
            $o .= html_writer::start_tag('div', array('id' => 'ontop_area', 'class' => 'section0_ontop'));
            $o .= html_writer::start_tag('ul', array('id' => 'ontop_area', 'class' => 'topics'));

            // 0-section is displayed a little different then the others
            if ($section0->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                $o .= $this->section0_ontop_header($section0, $course, false, 0);
                $o .= $this->courserenderer->course_section_cm_list($course, $section0, 0);
                $o .= $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                $o .= $this->section_footer();
            }
        } else {
            $o .= html_writer::start_tag('div', array('id' => 'ontop_area'));
            $o .= html_writer::start_tag('ul', array('id' => 'ontop_area', 'class' => 'topics'));
        }

        $o .= $this->end_section_list();
        $o .= html_writer::end_tag('div');
        return $o;
    }

    // Render the sections of a course
    public function render_sections($course, $sections, $format_options, $modinfo, $context){
        global $PAGE;

        $o = '';

        // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        $o .= html_writer::start_tag('div', array('id' => 'inline_area'));
        if (!$format_options['section0_ontop'] and ($thissection->summary or ! empty($modinfo->sections[0]) or $this->userisediting)) {
            $o .= $this->section_header($thissection, $course, false, 0);
            $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
            $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            $o .= $this->section_footer();
        }
        $o .= html_writer::end_tag('div');


        $shownonetoggle = false;
        $coursenumsections = $this->get_last_section_number();

        if ($coursenumsections > 0) {
            $sectiondisplayarray = array();
            $numsections = $coursenumsections; // Because we want to manipulate this for column breakpoints.

            if ($coursenumsections > 1) {
                if (($this->userisediting) || ($this->tcsettings['onesection'] == 1)) {
                    // Collapsed Topics all toggles.
                    $o .= $this->toggle_all();
                }
                if ($this->tcsettings['displayinstructions'] == 2) {
                    // Collapsed Topics instructions.
                    $o .= $this->display_instructions();
                }
            }
            $currentsectionfirst = false;
            $section = 1;

            $o .= $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                $o .= html_writer::start_tag('div', array('class' => $this->get_row_class()));
            }
            $o .= $this->start_toggle_section_list();

            $loopsection = 1;
            $breaking = false; // Once the first section is shown we can decide if we break on another column.
            $coursenumsections = 11;
            while ($loopsection <= $coursenumsections) {
                $thissection = $modinfo->get_section_info($section);

                /* Show the section if the user is permitted to access it, OR if it's not available
                  but there is some available info text which explains the reason & should display. */
                $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));

                if (($currentsectionfirst == true) && ($showsection == true)) {
                    // Show the section if we were meant to and it is the current section:....
                    $showsection = ($course->marker == $section);
                }

                if (!$showsection) {
                    // Hidden section message is overridden by 'unavailable' control.
                    if (!$course->hiddensections && $thissection->available) {
                        $thissection->ishidden = true;
                        $sectiondisplayarray[] = $thissection;
                    }
                } else {
                    if ($this->isoldtogglepreference == true) {
                        $togglestate = substr($this->togglelib->get_toggles(), $section, 1);
                        if ($togglestate == '1') {
                            $thissection->toggle = true;
                        } else {
                            $thissection->toggle = false;
                        }
                    } else {
                        $thissection->toggle = $this->togglelib->get_toggle_state($thissection->section);
                    }

                    if ($this->courseformat->is_section_current($thissection)) {
                        $this->currentsection = $thissection->section;
                        $thissection->toggle = true; // Open current section regardless of toggle state.
                        $this->togglelib->set_toggle_state($thissection->section, true);
                    }

                    $thissection->isshown = true;
                    $sectiondisplayarray[] = $thissection;
                }

                $section++;

                $loopsection++;
                if (($currentsectionfirst == true) && ($loopsection > $coursenumsections)) {
                    // Now show the rest.
                    $currentsectionfirst = false;
                    $loopsection = 1;
                    $section = 1;
                }
            }

            $shownsectioncount = 0;
            if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2) && (!empty($this->currentsection))) {
                $shownonetoggle = $this->currentsection; // One toggle open only, so as we have a current section it will be it.
            }
            foreach ($sectiondisplayarray as $thissection) {
                $shownsectioncount++;

                if (!empty($thissection->ishidden)) {
                    $o .= $this->section_hidden($thissection);
                } else if (!empty($thissection->issummary)) {
                    $o .= $this->section_summary($thissection, $course, null);
                } else if (!empty($thissection->isshown)) {
                    $o .= $this->section_header($thissection, $course, false, 0);
                    if ($thissection->uservisible) {
                        $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                        $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
                    }
                    $o .= html_writer::end_tag('div');
                    $o .= $this->section_footer();
                }
                unset($sections[$thissection->section]);
            }
        }
        $o .= $this->end_section_list();

        // Now initialise the JavaScript.
        $toggles = $this->togglelib->get_toggles();
        $this->page->requires->js_init_call('M.format_tabbedtopcoll.init', array(
            $course->id,
            $toggles,
            $coursenumsections,
            $this->defaulttogglepersistence,
            $this->defaultuserpreference,
            ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)),
            $shownonetoggle,
            $this->userisediting));

        // Make sure the database has the correct state of the toggles if changed by the code.
        // This ensures that a no-change page reload is correct.
        set_user_preference('topcoll_toggle_'.$course->id, $toggles);

        return $o;
    }
    public function render_sections1($course, $sections, $format_options, $modinfo, $context){
        global $PAGE;

        $o = '';

        // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        $o .= html_writer::start_tag('div', array('id' => 'inline_area'));
        if (!$format_options['section0_ontop'] and ($thissection->summary or ! empty($modinfo->sections[0]) or $this->userisediting)) {
            $o .= $this->section_header($thissection, $course, false, 0);
            $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
            $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            $o .= $this->section_footer();
        }
        $o .= html_writer::end_tag('div');


        $shownonetoggle = false;
        $coursenumsections = $this->get_last_section_number();

        if ($coursenumsections > 0) {
            $sectiondisplayarray = array();
            $numsections = $coursenumsections; // Because we want to manipulate this for column breakpoints.

            if ($coursenumsections > 1) {
                if (($this->userisediting) || ($this->tcsettings['onesection'] == 1)) {
                    // Collapsed Topics all toggles.
                    $o .= $this->toggle_all();
                }
                if ($this->tcsettings['displayinstructions'] == 2) {
                    // Collapsed Topics instructions.
                    $o .= $this->display_instructions();
                }
            }
            $currentsectionfirst = false;
            $section = 1;



            $o .= $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                $o .= html_writer::start_tag('div', array('class' => $this->get_row_class()));
            }
            $o .= $this->start_toggle_section_list();

            $loopsection = 1;
            $breaking = false; // Once the first section is shown we can decide if we break on another column.
            $coursenumsections = 11;
            while ($loopsection <= $coursenumsections) {
                $thissection = $modinfo->get_section_info($section);

                /* Show the section if the user is permitted to access it, OR if it's not available
                  but there is some available info text which explains the reason & should display. */
                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $showsection = $thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                } else {
                    $showsection = ($thissection->uservisible ||
                            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                        ($nextweekdate <= $timenow);
                }
                if (($currentsectionfirst == true) && ($showsection == true)) {
                    // Show the section if we were meant to and it is the current section:....
                    $showsection = ($course->marker == $section);
                } else if (($this->tcsettings['layoutstructure'] == 4) &&
                    ($course->marker == $section) && (!$this->userisediting)) {
                    $showsection = false; // Do not reshow current section.
                }
                if (!$showsection) {
                    // Hidden section message is overridden by 'unavailable' control.
                    $testhidden = false;
                    if ($this->tcsettings['layoutstructure'] != 4) {
                        if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                            $testhidden = true;
                        } else if ($nextweekdate <= $timenow) {
                            $testhidden = true;
                        }
                    } else {
                        if (($currentsectionfirst == true) && ($course->marker == $section)) {
                            $testhidden = true;
                        } else if (($currentsectionfirst == false) && ($course->marker != $section)) {
                            $testhidden = true;
                        }
                    }
                    if ($testhidden) {
                        if (!$course->hiddensections && $thissection->available) {
                            $thissection->ishidden = true;
                            $sectiondisplayarray[] = $thissection;
                        }
                    }
                } else {
                    if ($this->isoldtogglepreference == true) {
                        $togglestate = substr($this->togglelib->get_toggles(), $section, 1);
                        if ($togglestate == '1') {
                            $thissection->toggle = true;
                        } else {
                            $thissection->toggle = false;
                        }
                    } else {
                        $thissection->toggle = $this->togglelib->get_toggle_state($thissection->section);
                    }

                    if ($this->courseformat->is_section_current($thissection)) {
                        $this->currentsection = $thissection->section;
                        $thissection->toggle = true; // Open current section regardless of toggle state.
                        $this->togglelib->set_toggle_state($thissection->section, true);
                    }

                    $thissection->isshown = true;
                    $sectiondisplayarray[] = $thissection;
                }

                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $section++;
                } else {
                    $section--;
                    if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                        $weekdate = $nextweekdate;
                    }
                }

                $loopsection++;
                if (($currentsectionfirst == true) && ($loopsection > $coursenumsections)) {
                    // Now show the rest.
                    $currentsectionfirst = false;
                    $loopsection = 1;
                    $section = 1;
                }
                if ($section > $coursenumsections) {
                    // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                    break;
                }
            }

            $canbreak = ($this->tcsettings['layoutcolumns'] > 1);
            $columncount = 1;
            $breakpoint = 0;
            $shownsectioncount = 0;
            if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2) && (!empty($this->currentsection))) {
                $shownonetoggle = $this->currentsection; // One toggle open only, so as we have a current section it will be it.
            }
            foreach ($sectiondisplayarray as $thissection) {
                $shownsectioncount++;

                if (!empty($thissection->ishidden)) {
                    $o .= $this->section_hidden($thissection);
                } else if (!empty($thissection->issummary)) {
                    $o .= $this->section_summary($thissection, $course, null);
                } else if (!empty($thissection->isshown)) {
                    if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)) {
                        if ($thissection->toggle) {
                            if (!empty($shownonetoggle)) {
                                // Make sure the current section is not closed if set above.
                                if ($shownonetoggle != $thissection->section) {
                                    // There is already a toggle open so others need to be closed.
                                    $thissection->toggle = false;
                                    $this->togglelib->set_toggle_state($thissection->section, false);
                                }
                            } else {
                                // No open toggle, so as this is the first, it can be the one.
                                $shownonetoggle = $thissection->section;
                            }
                        }
                    }
                    $o .= $this->section_header($thissection, $course, false, 0);
                    if ($thissection->uservisible) {
                        $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                        $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
                    }
                    $o .= html_writer::end_tag('div');
                    $o .= $this->section_footer();
                }

                // Only check for breaking up the structure with rows if more than one column and when we output all of the sections.
                if ($canbreak === true) {
                    // Only break in non-mobile themes or using a responsive theme.
                    if ((!$this->formatresponsive) || ($this->mobiletheme === false)) {
                        if ($this->tcsettings['layoutcolumnorientation'] == 1) {  // Vertical mode.
                            // This is not perfect yet as does not tally the shown sections and divide by columns.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // Divide the number of sections by the number of columns.
                                $breakpoint = $numsections / $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint) &&
                                ($columncount < $this->tcsettings['layoutcolumns'])) {
                                $o .= $this->end_section_list();
                                $o .= $this->start_toggle_section_list();
                                $columncount++;
                                // Next breakpoint is...
                                $breakpoint += $numsections / $this->tcsettings['layoutcolumns'];
                            }
                        } else {  // Horizontal mode.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // The lowest value here for layoutcolumns is 2 and the maximum for shownsectioncount is 2, so :).
                                $breakpoint = $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint)) {
                                $o .= $this->end_section_list();
                                $o .= $this->start_toggle_section_list();
                                // Next breakpoint is...
                                $breakpoint += $this->tcsettings['layoutcolumns'];
                            }
                        }
                    }
                }
                unset($sections[$thissection->section]);
            }
        }

        if ($this->userisediting and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $coursenumsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                $o .= $this->stealth_section_header($section);
                $o .= $this->courserenderer->course_section_cm_list($course, $thissection->section, 0);
                $o .= $this->stealth_section_footer();
            }

            $o .= $this->end_section_list();

            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                $o .= html_writer::end_tag('div');
            }

            $o .= $this->change_number_sections($course, 0);
        } else {
            $o .= $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                $o .= html_writer::end_tag('div');
            }
        }

        // Now initialise the JavaScript.
        $toggles = $this->togglelib->get_toggles();
        $this->page->requires->js_init_call('M.format_tabbedtopcoll.init', array(
            $course->id,
            $toggles,
            $coursenumsections,
            $this->defaulttogglepersistence,
            $this->defaultuserpreference,
            ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)),
            $shownonetoggle,
            $this->userisediting));

        // Make sure the database has the correct state of the toggles if changed by the code.
        // This ensures that a no-change page reload is correct.
        set_user_preference('topcoll_toggle_'.$course->id, $toggles);

        return $o;
    }
    public function render_sections0($course, $sections, $format_options, $modinfo, $context){
        global $PAGE;

        $o = '';

        // General section if non-empty.
        $thissection = $sections[0];
        unset($sections[0]);
        $o .= html_writer::start_tag('div', array('id' => 'inline_area'));
        if (!$format_options['section0_ontop'] and ($thissection->summary or ! empty($modinfo->sections[0]) or $this->userisediting)) {
            $o .= $this->section_header($thissection, $course, false, 0);
            $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
            $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
            $o .= $this->section_footer();
        }
        $o .= html_writer::end_tag('div');


        $shownonetoggle = false;
//        $coursenumsections = $this->courseformat->get_last_section_number();
        $coursenumsections = $this->get_last_section_number();

        if ($coursenumsections > 0) {
            $sectiondisplayarray = array();
            $numsections = $coursenumsections; // Because we want to manipulate this for column breakpoints.

            if ($coursenumsections > 1) {
                if (($this->userisediting) || ($this->tcsettings['onesection'] == 1)) {
                    // Collapsed Topics all toggles.
                    $o .= $this->toggle_all();
                }
                if ($this->tcsettings['displayinstructions'] == 2) {
                    // Collapsed Topics instructions.
                    $o .= $this->display_instructions();
                }
            }
            $currentsectionfirst = false;

            if (($this->tcsettings['layoutstructure'] == 4) && (!$this->userisediting)) {
                $currentsectionfirst = true;
            }

            if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                $section = 1;
            } else {
                $timenow = time();
                $weekofseconds = 604800;
                $course->enddate = $course->startdate + ($weekofseconds * $coursenumsections);
                $section = $coursenumsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                $loopsection = 1;
                $numsections = 0;
                while ($loopsection <= $coursenumsections) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                    if ((($thissection->uservisible ||
                                ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                            ($nextweekdate <= $timenow)) == true) {
                        $numsections++; // Section not shown so do not count in columns calculation.
                    }
                    $weekdate = $nextweekdate;
                    $section--;
                    $loopsection++;
                }
                // Reset.
                $section = $coursenumsections;
                $weekdate = $course->enddate;      // This should be 0:00 Monday of that week.
                $weekdate -= 7200;                 // Subtract two hours to avoid possible DST problems.
            }

            if ($numsections < $this->tcsettings['layoutcolumns']) {
                $this->tcsettings['layoutcolumns'] = $numsections;  // Help to ensure a reasonable display.
            }
            if (($this->tcsettings['layoutcolumns'] > 1) && ($this->mobiletheme === false)) {
                if ($this->tcsettings['layoutcolumns'] > 4) {
                    // Default in config.php (and reset in database) or database has been changed incorrectly.
                    $this->tcsettings['layoutcolumns'] = 4;

                    // Update....
                    $this->courseformat->update_tabbedtopcoll_columns_setting($this->tcsettings['layoutcolumns']);
                    $this->courseformat->update_tabbedtopcoll_columns_setting($this->tcsettings['layoutcolumns']);
                }

                if (($this->tablettheme === true) && ($this->tcsettings['layoutcolumns'] > 2)) {
                    // Use a maximum of 2 for tablets.
                    $this->tcsettings['layoutcolumns'] = 2;
                }

                if ($this->formatresponsive) {
                    $this->tccolumnwidth = 100 / $this->tcsettings['layoutcolumns'];
                    if ($this->tcsettings['layoutcolumnorientation'] == 2) { // Horizontal column layout.
                        $this->tccolumnwidth -= 0.5;
                        $this->tccolumnpadding = 0; // In 'px'.
                    } else {
                        $this->tccolumnwidth -= 0.2;
                        $this->tccolumnpadding = 0; // In 'px'.
                    }
                }
            } else if ($this->tcsettings['layoutcolumns'] < 1) {
                // Distributed default in plugin settings (and reset in database) or database has been changed incorrectly.
                $this->tcsettings['layoutcolumns'] = 1;

                // Update....
                $this->courseformat->update_tabbedtopcoll_columns_setting($this->tcsettings['layoutcolumns']);
                $this->courseformat->update_tabbedtopcoll_columns_setting($this->tcsettings['layoutcolumns']);
            }

            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                $o .= html_writer::start_tag('div', array('class' => $this->get_row_class()));
            }
            $o .= $this->start_toggle_section_list();

            $loopsection = 1;
            $breaking = false; // Once the first section is shown we can decide if we break on another column.
            $coursenumsections = 11;
            while ($loopsection <= $coursenumsections) {
                if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                    $nextweekdate = $weekdate - ($weekofseconds);
                }
                $thissection = $modinfo->get_section_info($section);

                /* Show the section if the user is permitted to access it, OR if it's not available
                  but there is some available info text which explains the reason & should display. */
                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $showsection = $thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                } else {
                    $showsection = ($thissection->uservisible ||
                            ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo))) &&
                        ($nextweekdate <= $timenow);
                }
                if (($currentsectionfirst == true) && ($showsection == true)) {
                    // Show the section if we were meant to and it is the current section:....
                    $showsection = ($course->marker == $section);
                } else if (($this->tcsettings['layoutstructure'] == 4) &&
                    ($course->marker == $section) && (!$this->userisediting)) {
                    $showsection = false; // Do not reshow current section.
                }
                if (!$showsection) {
                    // Hidden section message is overridden by 'unavailable' control.
                    $testhidden = false;
                    if ($this->tcsettings['layoutstructure'] != 4) {
                        if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                            $testhidden = true;
                        } else if ($nextweekdate <= $timenow) {
                            $testhidden = true;
                        }
                    } else {
                        if (($currentsectionfirst == true) && ($course->marker == $section)) {
                            $testhidden = true;
                        } else if (($currentsectionfirst == false) && ($course->marker != $section)) {
                            $testhidden = true;
                        }
                    }
                    if ($testhidden) {
                        if (!$course->hiddensections && $thissection->available) {
                            $thissection->ishidden = true;
                            $sectiondisplayarray[] = $thissection;
                        }
                    }
                } else {
                    if ($this->isoldtogglepreference == true) {
                        $togglestate = substr($this->togglelib->get_toggles(), $section, 1);
                        if ($togglestate == '1') {
                            $thissection->toggle = true;
                        } else {
                            $thissection->toggle = false;
                        }
                    } else {
                        $thissection->toggle = $this->togglelib->get_toggle_state($thissection->section);
                    }

                    if ($this->courseformat->is_section_current($thissection)) {
                        $this->currentsection = $thissection->section;
                        $thissection->toggle = true; // Open current section regardless of toggle state.
                        $this->togglelib->set_toggle_state($thissection->section, true);
                    }

                    $thissection->isshown = true;
                    $sectiondisplayarray[] = $thissection;
                }

                if (($this->tcsettings['layoutstructure'] != 3) || ($this->userisediting)) {
                    $section++;
                } else {
                    $section--;
                    if (($this->tcsettings['layoutstructure'] == 3) && ($this->userisediting == false)) {
                        $weekdate = $nextweekdate;
                    }
                }

                $loopsection++;
                if (($currentsectionfirst == true) && ($loopsection > $coursenumsections)) {
                    // Now show the rest.
                    $currentsectionfirst = false;
                    $loopsection = 1;
                    $section = 1;
                }
                if ($section > $coursenumsections) {
                    // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                    break;
                }
            }

            $canbreak = ($this->tcsettings['layoutcolumns'] > 1);
            $columncount = 1;
            $breakpoint = 0;
            $shownsectioncount = 0;
            if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2) && (!empty($this->currentsection))) {
                $shownonetoggle = $this->currentsection; // One toggle open only, so as we have a current section it will be it.
            }
            foreach ($sectiondisplayarray as $thissection) {
                $shownsectioncount++;

                if (!empty($thissection->ishidden)) {
                    $o .= $this->section_hidden($thissection);
                } else if (!empty($thissection->issummary)) {
                    $o .= $this->section_summary($thissection, $course, null);
                } else if (!empty($thissection->isshown)) {
                    if ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)) {
                        if ($thissection->toggle) {
                            if (!empty($shownonetoggle)) {
                                // Make sure the current section is not closed if set above.
                                if ($shownonetoggle != $thissection->section) {
                                    // There is already a toggle open so others need to be closed.
                                    $thissection->toggle = false;
                                    $this->togglelib->set_toggle_state($thissection->section, false);
                                }
                            } else {
                                // No open toggle, so as this is the first, it can be the one.
                                $shownonetoggle = $thissection->section;
                            }
                        }
                    }
                    $o .= $this->section_header($thissection, $course, false, 0);
                    if ($thissection->uservisible) {
                        $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                        $o .= $this->courserenderer->course_section_add_cm_control($course, $thissection->section, 0);
                    }
                    $o .= html_writer::end_tag('div');
                    $o .= $this->section_footer();
                }

                // Only check for breaking up the structure with rows if more than one column and when we output all of the sections.
                if ($canbreak === true) {
                    // Only break in non-mobile themes or using a responsive theme.
                    if ((!$this->formatresponsive) || ($this->mobiletheme === false)) {
                        if ($this->tcsettings['layoutcolumnorientation'] == 1) {  // Vertical mode.
                            // This is not perfect yet as does not tally the shown sections and divide by columns.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // Divide the number of sections by the number of columns.
                                $breakpoint = $numsections / $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint) &&
                                ($columncount < $this->tcsettings['layoutcolumns'])) {
                                $o .= $this->end_section_list();
                                $o .= $this->start_toggle_section_list();
                                $columncount++;
                                // Next breakpoint is...
                                $breakpoint += $numsections / $this->tcsettings['layoutcolumns'];
                            }
                        } else {  // Horizontal mode.
                            if (($breaking == false) && ($showsection == true)) {
                                $breaking = true;
                                // The lowest value here for layoutcolumns is 2 and the maximum for shownsectioncount is 2, so :).
                                $breakpoint = $this->tcsettings['layoutcolumns'];
                            }

                            if (($breaking == true) && ($shownsectioncount >= $breakpoint)) {
                                $o .= $this->end_section_list();
                                $o .= $this->start_toggle_section_list();
                                // Next breakpoint is...
                                $breakpoint += $this->tcsettings['layoutcolumns'];
                            }
                        }
                    }
                }
                unset($sections[$thissection->section]);
            }
        }

        if ($this->userisediting and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $coursenumsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                echo $this->courserenderer->course_section_cm_list($course, $thissection->section, 0);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }

            echo $this->change_number_sections($course, 0);
        } else {
            echo $this->end_section_list();
            if ((!$this->formatresponsive) && ($this->tcsettings['layoutcolumnorientation'] == 1)) { // Vertical columns.
                echo html_writer::end_tag('div');
            }
        }

        // Now initialise the JavaScript.
        $toggles = $this->togglelib->get_toggles();
        $this->page->requires->js_init_call('M.format_tabbedtopcoll.init', array(
            $course->id,
            $toggles,
            $coursenumsections,
            $this->defaulttogglepersistence,
            $this->defaultuserpreference,
            ((!$this->userisediting) && ($this->tcsettings['onesection'] == 2)),
            $shownonetoggle,
            $this->userisediting));

        // Make sure the database has the correct state of the toggles if changed by the code.
        // This ensures that a no-change page reload is correct.
        set_user_preference('topcoll_toggle_'.$course->id, $toggles);

        return $o;
    }

    public function render_sections_topics($course, $sections, $format_options, $modinfo, $numsections){
        global $PAGE;

        $o = '';

        foreach ($sections as $section => $thissection) {
            if ($section == 0) {
                $o .= html_writer::start_tag('div', array('id' => 'inline_area'));
                if($format_options['section0_ontop']){ // section-0 is already shown on top
                    $o .= html_writer::end_tag('div');
                    continue;
                }
                // 0-section is displayed a little different then the others
                if ($thissection->summary or !empty($modinfo->sections[0]) or $PAGE->user_is_editing()) {
                    $o .= $this->section_header($thissection, $course, false, 0);
                    $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    $o .= $this->courserenderer->course_section_add_cm_control($course, 0, 0);
                    $o .= $this->section_footer();
                }
                $o .= html_writer::end_tag('div');
                continue;
            }
            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }

            if (!$PAGE->user_is_editing() && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
                // Display section summary only.
                $o .= $this->section_summary($thissection, $course, null);
            } else {
                $o .= $this->section_header($thissection, $course, false, 0);
                if ($thissection->uservisible) {
                    $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                    $o .= $this->courserenderer->course_section_add_cm_control($course, $section, 0);
                }
                $o .= $this->section_footer();
            }
        }
        return $o;
    }

    // Render hidden sections for course editors only
    public function render_hidden_sections($course, $sections, $context, $modinfo, $numsections) {
        global $PAGE;
        $o ='';
        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($sections as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                $o .= $this->stealth_section_header($section);
                $o .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                $o .= $this->stealth_section_footer();
            }
            $o .= $this->change_number_sections($course, 0);
        }
        return $o;
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {
        $o = '';

        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
        if (!empty($controls)) {
            $o .= $this->section_edit_control_menu($controls, $course, $section);
        } else if (!$onsectionpage) {
            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }
            $url = new moodle_url('/course/view.php', array('id' => $course->id, 'section' => $section->section));
            // Get the specific words from the language files.
            $topictext = null;
            if (($this->tcsettings['layoutstructure'] == 1) || ($this->tcsettings['layoutstructure'] == 4)) {
                $topictext = get_string('setlayoutstructuretopic', 'format_topcoll');
            } else if (($this->tcsettings['layoutstructure'] == 2) || ($this->tcsettings['layoutstructure'] == 3)) {
                $topictext = get_string('setlayoutstructureweek', 'format_topcoll');
            } else {
                $topictext = get_string('setlayoutstructureday', 'format_topcoll');
            }
            $title = get_string('viewonly', 'format_topcoll', array('sectionname' => $topictext.' '.$section->section));
        }

        return $o;
    }

    /**
     * Generate the content to displayed on the left part of a section
     * before course modules are included.
     *
     * @param stdClass $section The course_section entry from DB.
     * @param stdClass $course The course entry from DB.
     * @param bool $onsectionpage true if being printed on a section page.
     * @return string HTML to output.
     */
    protected function section_left_content($section, $course, $onsectionpage) {
        $o = '';

        if (($section->section != 0) && (!$onsectionpage)) {
            // Only in the non-general sections.
            if ($this->courseformat->is_section_current($section)) {
                $o .= get_accesshide(get_string('currentsection', 'format_' . $course->format));
            }
            if (empty($this->tcsettings)) {
                $this->tcsettings = $this->courseformat->get_settings();
            }
        }
        return $o;
    }

    /**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        global $DB, $CFG, $PAGE;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        $options = $DB->get_records('course_format_options', array('courseid' => $course->id));
        $format_options=array();
        foreach($options as $option) {
            $format_options[$option->name] =$option->value;
        }

        if(isset($format_options['maxtabs'])){
            $max_tabs = $format_options['maxtabs'];
        } else { // Allow up to 5 tabs  by default if nothing else is set in the config file
            $max_tabs = (isset($CFG->max_tabs) ? $CFG->max_tabs : 5);
        }
        $coursecontext = context_course::instance($course->id);

        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $controls = array();

        // add move to/from top for section0 only
        if ($section->section === 0) {
            $controls['ontop'] = array(
                "icon" => 't/up',
                'name' => 'Show always on top',

                'attr' => array(
                    'tabnr' => 0,
                    'class' => 'ontop_mover',
                    'title' => 'Show always on top',
                    'data-action' => 'sectionzeroontop'
                )
            );
            $controls['inline'] = array(
                "icon" => 't/down',
                'name' => 'Show inline',

                'attr' => array(
                    'tabnr' => 0,
                    'class' => 'inline_mover',
                    'title' => 'Show inline',
                    'data-action' => 'sectionzeroinline'
                )
            );
        }

        // Insert tab moving menu items
        $controls['no_tab'] = array(
            "icon" => 't/left',
            'name' => 'Remove from Tabs',

            'attr' => array(
                'tabnr' => 0,
                'class' => 'tab_mover',
                'title' => 'Remove from Tabs',
                'data-action' => 'removefromtabs'
            )
        );

        $itemtitle = "Move to Tab ";
        $actions = array('movetotabzero', 'movetotabone', 'movetotabtwo','movetotabthree','movetotabfour','movetotabfive','movetotabsix','movetotabseven','movetotabeight','movetotabnine','movetotabten', 'sectionzeroontop', 'sectionzeroinline');
        for($i = 1; $i <= $max_tabs; $i++) {
            $tabname = 'tab'.$i.'_title';
            $itemname = 'To Tab "'.($course->$tabname ? $course->$tabname : $i).'"';

            $controls['to_tab'.$i] = array(
                "icon" => 't/right',
                'name' => $itemname,

                'attr' => array(
                    'tabnr' => $i,
                    'class' => 'tab_mover',
                    'title' => $itemtitle,
                    'data-action' => $actions[$i]
                )
            );
        }

        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
            if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                $url->param('marker', 0);
                $markedthistopic = get_string('markedthistopic');
                $highlightoff = get_string('highlightoff');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marked',
                    'name' => $highlightoff,
                    'pixattr' => array('class' => '', 'alt' => $markedthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markedthistopic,
                        'data-action' => 'removemarker'));
            } else {
                $url->param('marker', $section->section);
                $markthistopic = get_string('markthistopic');
                $highlight = get_string('highlight');
                $controls['highlight'] = array('url' => $url, "icon" => 'i/marker',
                    'name' => $highlight,
                    'pixattr' => array('class' => '', 'alt' => $markthistopic),
                    'attr' => array('class' => 'editing_highlight', 'title' => $markthistopic,
                        'data-action' => 'setmarker'));
            }
        }

        $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

        // If the edit key exists, we are going to insert our controls after it.
        if (array_key_exists("edit", $parentcontrols)) {
            $merged = array();
            // We can't use splice because we are using associative arrays.
            // Step through the array and merge the arrays.
            foreach ($parentcontrols as $key => $action) {
                $merged[$key] = $action;
                if ($key == "edit") {
                    // If we have come to the edit key, merge these controls here.
                    $merged = array_merge($merged, $controls);
                }
            }

            return $merged;
        } else {
            return array_merge($controls, $parentcontrols);
        }
    }

    protected function change_number_sections($course, $sectionreturn = null) {
        $coursecontext = context_course::instance($course->id);
        if (!has_capability('moodle/course:update', $coursecontext)) {
            return '';
        }

        $o = '';
        $format = course_get_format($course);
        $options = $format->get_format_options();
        $maxsections = $format->get_max_sections();
        $lastsection = $format->get_last_section_number();
//        $supportsnumsections = array_key_exists('numsections', $options);
        $supportsnumsections = false;

        if ($supportsnumsections) {
            // Current course format has 'numsections' option, which is very confusing and we suggest course format
            // developers to get rid of it (see MDL-57769 on how to do it).
            // Display "Increase section" / "Decrease section" links.

            $o .= html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            if ($lastsection < $maxsections) {
                $straddsection = get_string('increasesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                        'increase' => true,
                        'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
                $o .= html_writer::link($url, $icon.get_accesshide($straddsection), array('class' => 'increase-sections'));
            }

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                        'increase' => false,
                        'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                $o .= html_writer::link($url, $icon.get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            $o .= html_writer::end_tag('div');

        } else if (course_get_format($course)->uses_sections()) {
            if ($lastsection >= $maxsections) {
                // Don't allow more sections if we already hit the limit.
                return '';
            }
            // Current course format does not have 'numsections' option but it has multiple sections suppport.
            // Display the "Add section" link that will insert a section in the end.
            // Note to course format developers: inserting sections in the other positions should check both
            // capabilities 'moodle/course:update' and 'moodle/course:movesections'.
            $o .= html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));
            if (get_string_manager()->string_exists('addsections', 'format_'.$course->format)) {
                $straddsections = get_string('addsections', 'format_'.$course->format);
            } else {
                $straddsections = get_string('addsections');
            }
            $url = new moodle_url('/course/changenumsections.php',
                ['courseid' => $course->id, 'insertsection' => 0, 'sesskey' => sesskey()]);
            if ($sectionreturn !== null) {
                $url->param('sectionreturn', $sectionreturn);
            }
            $icon = $this->output->pix_icon('t/add', $straddsections);
            $newsections = $maxsections - $lastsection;
            $o .= html_writer::link($url, $icon . $straddsections,
                array('class' => 'add-sections', 'data-add-sections' => $straddsections, 'new-sections' => $newsections));
            $o .= html_writer::end_tag('div');
        }
        return $o;
    }

    // A numsections free version
    public function get_last_section_number() {
        global $PAGE, $DB;
        $sectionrecords = $DB->get_records('course_sections', array('course' => $PAGE->course->id));
        return count((array)$sectionrecords);
    }

}
