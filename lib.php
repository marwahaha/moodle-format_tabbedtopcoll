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
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.md' file.
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

require_once($CFG->dirroot . '/course/format/topcoll/lib.php'); // For format_base.

class format_tabbedtopcoll extends format_topcoll {

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * Collapsed Topics format uses the following options (until extras are migrated):
     * - numsections
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        global $CFG;
//        $max_tabs = (isset($CFG->max_tabs) ? $CFG->max_tabs : 5);
        $max_tabs = 9; // Currently there is a maximum of 9 tabs!
        static $courseformatoptions = false;
        $courseconfig = null;

        if ($courseformatoptions === false) {
            /* Note: Because 'admin_setting_configcolourpicker' in 'settings.php' needs to use a prefixing '#'
                     this needs to be stripped off here if it's there for the format's specific colour picker. */
            $defaulttgfgcolour = get_config('format_topcoll', 'defaulttgfgcolour');
            if ($defaulttgfgcolour[0] == '#') {
                $defaulttgfgcolour = substr($defaulttgfgcolour, 1);
            }
            $defaulttgfghvrcolour = get_config('format_topcoll', 'defaulttgfghvrcolour');
            if ($defaulttgfghvrcolour[0] == '#') {
                $defaulttgfghvrcolour = substr($defaulttgfghvrcolour, 1);
            }
            $defaulttgbgcolour = get_config('format_topcoll', 'defaulttgbgcolour');
            if ($defaulttgbgcolour[0] == '#') {
                $defaulttgbgcolour = substr($defaulttgbgcolour, 1);
            }
            $defaulttgbghvrcolour = get_config('format_topcoll', 'defaulttgbghvrcolour');
            if ($defaulttgbghvrcolour[0] == '#') {
                $defaulttgbghvrcolour = substr($defaulttgbghvrcolour, 1);
            }

            $courseconfig = get_config('moodlecourse');

            $courseid = $this->get_courseid();
            if ($courseid == 1) { // New course.
                $defaultnumsections = $courseconfig->numsections;
            } else { // Existing course that may not have 'numsections' - see get_last_section().
                global $DB;
                $defaultnumsections = $DB->get_field_sql('SELECT max(section) from {course_sections}
                    WHERE course = ?', array($courseid));
            }
            $courseformatoptions = array(
                'maxtabs' => array(
                    'default' => (isset($CFG->max_tabs) ? $CFG->max_tabs : 5),
                    'type' => PARAM_INT,
                    'element_type' => 'hidden',
                ),
                'numsections' => array(
                    'default' => $defaultnumsections,
                    'type' => PARAM_INT,
                ),
                'hiddensections' => array(
                    'default' => $courseconfig->hiddensections,
                    'type' => PARAM_INT,
                ),

                'section0_ontop' => array(
                    'default' => false,
                    'type' => PARAM_BOOL,
                    'label' => '',
                ),
                'single_section_tabs' => array(
                    'default' => get_config('format_topcoll', 'defaultsectionnameastabname'),
                    'type' => PARAM_BOOL
                ),

                'displayinstructions' => array(
                    'default' => get_config('format_topcoll', 'defaultdisplayinstructions'),
                    'type' => PARAM_INT,
                ),
                'layoutelement' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutelement'),
                    'type' => PARAM_INT,
                ),
                'layoutstructure' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutstructure'),
                    'type' => PARAM_INT,
                ),
                'layoutcolumns' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutcolumns'),
                    'type' => PARAM_INT,
                ),
                'layoutcolumnorientation' => array(
                    'default' => get_config('format_topcoll', 'defaultlayoutcolumnorientation'),
                    'type' => PARAM_INT,
                ),
                'togglealignment' => array(
                    'default' => get_config('format_topcoll', 'defaulttogglealignment'),
                    'type' => PARAM_INT,
                ),
                'toggleiconposition' => array(
                    'default' => get_config('format_topcoll', 'defaulttoggleiconposition'),
                    'type' => PARAM_INT,
                ),
                'toggleiconset' => array(
                    'default' => get_config('format_topcoll', 'defaulttoggleiconset'),
                    'type' => PARAM_ALPHA,
                ),
                'onesection' => array(
                    'default' => get_config('format_topcoll', 'defaultonesection'),
                    'type' => PARAM_INT,
                ),
                'toggleallhover' => array(
                    'default' => get_config('format_topcoll', 'defaulttoggleallhover'),
                    'type' => PARAM_INT,
                ),
                'toggleforegroundcolour' => array(
                    'default' => $defaulttgfgcolour,
                    'type' => PARAM_ALPHANUM,
                ),
                'toggleforegroundopacity' => array(
                    'default' => get_config('format_topcoll', 'defaulttgfgopacity'),
                    'type' => PARAM_RAW,
                ),
                'toggleforegroundhovercolour' => array(
                    'default' => $defaulttgfghvrcolour,
                    'type' => PARAM_ALPHANUM,
                ),
                'toggleforegroundhoveropacity' => array(
                    'default' => get_config('format_topcoll', 'defaulttgbghvropacity'),
                    'type' => PARAM_RAW,
                ),
                'togglebackgroundcolour' => array(
                    'default' => $defaulttgbgcolour,
                    'type' => PARAM_ALPHANUM,
                ),
                'togglebackgroundopacity' => array(
                    'default' => get_config('format_topcoll', 'defaulttgbgopacity'),
                    'type' => PARAM_RAW,
                ),
                'togglebackgroundhovercolour' => array(
                    'default' => $defaulttgbghvrcolour,
                    'type' => PARAM_ALPHANUM,
                ),
                'togglebackgroundhoveropacity' => array(
                    'default' => get_config('format_topcoll', 'defaulttgbghvropacity'),
                    'type' => PARAM_RAW,
                ),
                'showsectionsummary' => array(
                    'default' => get_config('format_topcoll', 'defaultshowsectionsummary'),
                    'type' => PARAM_INT,
                )
            );

            // the sequence in which the tabs will be displayed
            $courseformatoptions['tab_seq'] = array('default' => '','type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);

            // now loop through the tabs but don't show them as we only need the DB records...
            $courseformatoptions['tab0_title'] = array('default' => get_string('tabzero_title', 'format_tabbedtopcoll'),'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
            $courseformatoptions['tab0'] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
            for ($i = 1; $i <= $max_tabs; $i++) {
                $courseformatoptions['tab'.$i.'_title'] = array('default' => "Tab ".$i,'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
                $courseformatoptions['tab'.$i] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
                $courseformatoptions['tab'.$i.'_sectionnums'] = array('default' => "",'type' => PARAM_TEXT,'label' => '','element_type' => 'hidden',);
            }
        }
        if ($foreditform && !isset($courseformatoptions['displayinstructions']['label'])) {
            /* Note: Because 'admin_setting_configcolourpicker' in 'settings.php' needs to use a prefixing '#'
                     this needs to be stripped off here if it's there for the format's specific colour picker. */
            $defaulttgfgcolour = get_config('format_topcoll', 'defaulttgfgcolour');
            if ($defaulttgfgcolour[0] == '#') {
                $defaulttgfgcolour = substr($defaulttgfgcolour, 1);
            }
            $defaulttgfghvrcolour = get_config('format_topcoll', 'defaulttgfghvrcolour');
            if ($defaulttgfghvrcolour[0] == '#') {
                $defaulttgfghvrcolour = substr($defaulttgfghvrcolour, 1);
            }
            $defaulttgbgcolour = get_config('format_topcoll', 'defaulttgbgcolour');
            if ($defaulttgbgcolour[0] == '#') {
                $defaulttgbgcolour = substr($defaulttgbgcolour, 1);
            }
            $defaulttgbghvrcolour = get_config('format_topcoll', 'defaulttgbghvrcolour');
            if ($defaulttgbghvrcolour[0] == '#') {
                $defaulttgbghvrcolour = substr($defaulttgbghvrcolour, 1);
            }

            $context = $this->get_context();

            if (is_null($courseconfig)) {
                $courseconfig = get_config('moodlecourse');
            }
            $sectionmenu = array();
            for ($i = 0; $i <= $courseconfig->maxsections; $i++) {
                $sectionmenu[$i] = "$i";
            }
            $courseformatoptionsedit = array(
                'numsections' => array(
                    'label' => new lang_string('numbersections', 'format_topcoll'),
                    'element_type' => 'select',
                    'element_attributes' => array($sectionmenu),
                ),
                'hiddensections' => array(
                    'label' => new lang_string('hiddensections'),
                    'help' => 'hiddensections',
                    'help_component' => 'moodle',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(0 => new lang_string('hiddensectionscollapsed'),
                            1 => new lang_string('hiddensectionsinvisible')
                        )
                    ),
                ),
                'section0_ontop' => array(
                    'label' => get_string('section0_label', 'format_tabbedtopcoll'),
                    'element_type' => 'advcheckbox',
                    'help' => 'section0',
                    'help_component' => 'format_tabbedtopcoll',
                    'element_type' => 'hidden',
                ),
                'single_section_tabs' => array(
                    'label' => get_string('single_section_tabs_label', 'format_tabbedtopcoll'),
                    'element_type' => 'advcheckbox',
                    'help' => 'single_section_tabs',
                    'help_component' => 'format_tabbedtopcoll',
                ),
                'displayinstructions' => array(
                    'label' => new lang_string('displayinstructions', 'format_topcoll'),
                    'help' => 'displayinstructions',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('no'),
                            2 => new lang_string('yes'))
                    )
                )
            );
            if (has_capability('format/tabbedtopcoll:changelayout', $context)) {
                $courseformatoptionsedit['layoutelement'] = array(
                    'label' => new lang_string('setlayoutelements', 'format_topcoll'),
                    'help' => 'setlayoutelements',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array( // In insertion order and not numeric for sorting purposes.
                        array(
                            // Toggle word, toggle section x and section number.
                            1 => new lang_string('setlayout_all', 'format_topcoll'),
                            // Toggle word and toggle section x.
                            3 => new lang_string('setlayout_toggle_word_section_x', 'format_topcoll'),
                            // Toggle word and section number.
                            2 => new lang_string('setlayout_toggle_word_section_number', 'format_topcoll'),
                            // Toggle section x and section number.
                            5 => new lang_string('setlayout_toggle_section_x_section_number', 'format_topcoll'),
                            // Toggle word.
                            4 => new lang_string('setlayout_toggle_word', 'format_topcoll'),
                            // Toggle section x.
                            8 => new lang_string('setlayout_toggle_section_x', 'format_topcoll'),
                            // Section number.
                            6 => new lang_string('setlayout_section_number', 'format_topcoll'),
                            // No additions.
                            7 => new lang_string('setlayout_no_additions', 'format_topcoll'))
                    )
                );
                $courseformatoptionsedit['layoutstructure'] = array(
                    'label' => new lang_string('setlayoutstructure', 'format_topcoll'),
                    'help' => 'setlayoutstructure',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            // Topic.
                            1 => new lang_string('setlayoutstructuretopic', 'format_topcoll'),
                            // Week.
                            2 => new lang_string('setlayoutstructureweek', 'format_topcoll'),
                            // Current Week First.
                            3 => new lang_string('setlayoutstructurelatweekfirst', 'format_topcoll'),
                            // Current Topic First.
                            4 => new lang_string('setlayoutstructurecurrenttopicfirst', 'format_topcoll'),
                            // Day.
                            5 => new lang_string('setlayoutstructureday', 'format_topcoll'))
                    )
                );
                $courseformatoptionsedit['layoutcolumns'] = array(
                    'label' => new lang_string('setlayoutcolumns', 'format_topcoll'),
                    'help' => 'setlayoutcolumns',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('one', 'format_topcoll'),   // Default.
                            2 => new lang_string('two', 'format_topcoll'),   // Two.
                            3 => new lang_string('three', 'format_topcoll'), // Three.
                            4 => new lang_string('four', 'format_topcoll'))  // Four.
                    )
                );
                $courseformatoptionsedit['layoutcolumnorientation'] = array(
                    'label' => new lang_string('setlayoutcolumnorientation', 'format_topcoll'),
                    'help' => 'setlayoutcolumnorientation',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('columnvertical', 'format_topcoll'),
                            2 => new lang_string('columnhorizontal', 'format_topcoll')) // Default.
                    )
                );
                $courseformatoptionsedit['toggleiconposition'] = array(
                    'label' => new lang_string('settoggleiconposition', 'format_topcoll'),
                    'help' => 'settoggleiconposition',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('left', 'format_topcoll'),   // Left.
                            2 => new lang_string('right', 'format_topcoll'))  // Right.
                    )
                );
                $courseformatoptionsedit['onesection'] = array(
                    'label' => new lang_string('onesection', 'format_topcoll'),
                    'help' => 'onesection',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('no'),
                            2 => new lang_string('yes'))
                    )
                );
                $courseformatoptionsedit['showsectionsummary'] = array(
                    'label' => new lang_string('setshowsectionsummary', 'format_topcoll'),
                    'help' => 'setshowsectionsummary',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('no'),
                            2 => new lang_string('yes'))
                    )
                );
            } else {
                $courseformatoptionsedit['layoutelement'] = array(
                    'label' => get_config('format_topcoll', 'defaultlayoutelement'), 'element_type' => 'hidden');
                $courseformatoptionsedit['layoutstructure'] = array(
                    'label' => get_config('format_topcoll', 'defaultlayoutstructure'), 'element_type' => 'hidden');
                $courseformatoptionsedit['layoutcolumns'] = array(
                    'label' => get_config('format_topcoll', 'defaultlayoutcolumns'), 'element_type' => 'hidden');
                $courseformatoptionsedit['layoutcolumnorientation'] = array(
                    'label' => get_config('format_topcoll', 'defaultlayoutcolumnorientation'), 'element_type' => 'hidden');
                $courseformatoptionsedit['toggleiconposition'] = array(
                    'label' => get_config('format_topcoll', 'defaulttoggleiconposition'), 'element_type' => 'hidden');
                $courseformatoptionsedit['onesection'] = array(
                    'label' => get_config('format_topcoll', 'defaultonesection'), 'element_type' => 'hidden');
                $courseformatoptionsedit['showsectionsummary'] = array(
                    'label' => get_config('format_topcoll', 'defaultshowsectionsummary'), 'element_type' => 'hidden');
            }

            if (has_capability('format/tabbedtopcoll:changetogglealignment', $context)) {
                $courseformatoptionsedit['togglealignment'] = array(
                    'label' => new lang_string('settogglealignment', 'format_topcoll'),
                    'help' => 'settogglealignment',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('left', 'format_topcoll'),   // Left.
                            2 => new lang_string('center', 'format_topcoll'), // Centre.
                            3 => new lang_string('right', 'format_topcoll'))  // Right.
                    )
                );
            } else {
                $courseformatoptionsedit['togglealignment'] = array(
                    'label' => get_config('format_topcoll', 'defaulttogglealignment'), 'element_type' => 'hidden');
            }

            if (has_capability('format/tabbedtopcoll:changetoggleiconset', $context)) {
                $courseformatoptionsedit['toggleiconset'] = array(
                    'label' => new lang_string('settoggleiconset', 'format_topcoll'),
                    'help' => 'settoggleiconset',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(
                            'arrow' => new lang_string('arrow', 'format_topcoll'),               // Arrow icon set.
                            'bulb' => new lang_string('bulb', 'format_topcoll'),                 // Bulb icon set.
                            'cloud' => new lang_string('cloud', 'format_topcoll'),               // Cloud icon set.
                            'eye' => new lang_string('eye', 'format_topcoll'),                   // Eye icon set.
                            'folder' => new lang_string('folder', 'format_topcoll'),             // Folder icon set.
                            'groundsignal' => new lang_string('groundsignal', 'format_topcoll'), // Ground signal set.
                            'led' => new lang_string('led', 'format_topcoll'),                   // LED icon set.
                            'point' => new lang_string('point', 'format_topcoll'),               // Point icon set.
                            'power' => new lang_string('power', 'format_topcoll'),               // Power icon set.
                            'radio' => new lang_string('radio', 'format_topcoll'),               // Radio icon set.
                            'smiley' => new lang_string('smiley', 'format_topcoll'),             // Smiley icon set.
                            'square' => new lang_string('square', 'format_topcoll'),             // Square icon set.
                            'sunmoon' => new lang_string('sunmoon', 'format_topcoll'),           // Sun / Moon icon set.
                            'switch' => new lang_string('switch', 'format_topcoll'))             // Switch icon set.
                    )
                );
                $courseformatoptionsedit['toggleallhover'] = array(
                    'label' => new lang_string('settoggleallhover', 'format_topcoll'),
                    'help' => 'settoggleallhover',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array(
                        array(1 => new lang_string('no'),
                            2 => new lang_string('yes'))
                    )
                );
            } else {
                $courseformatoptionsedit['toggleiconset'] = array(
                    'label' => get_config('format_topcoll', 'defaulttoggleiconset'), 'element_type' => 'hidden');
                $courseformatoptionsedit['toggleallhover'] = array(
                    'label' => get_config('format_topcoll', 'defaulttoggleallhover'), 'element_type' => 'hidden');
            }

            if (has_capability('format/tabbedtopcoll:changecolour', $context)) {
                $opacityvalues = array(
                    '0.0' => '0.0',
                    '0.1' => '0.1',
                    '0.2' => '0.2',
                    '0.3' => '0.3',
                    '0.4' => '0.4',
                    '0.5' => '0.5',
                    '0.6' => '0.6',
                    '0.7' => '0.7',
                    '0.8' => '0.8',
                    '0.9' => '0.9',
                    '1.0' => '1.0'
                );
                $courseformatoptionsedit['toggleforegroundcolour'] = array(
                    'label' => new lang_string('settoggleforegroundcolour', 'format_topcoll'),
                    'help' => 'settoggleforegroundcolour',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaulttgfgcolour)
                    )
                );
                $courseformatoptionsedit['toggleforegroundopacity'] = array(
                    'label' => new lang_string('settoggleforegroundopacity', 'format_topcoll'),
                    'help' => 'settoggleforegroundopacity',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues)
                );
                $courseformatoptionsedit['toggleforegroundhovercolour'] = array(
                    'label' => new lang_string('settoggleforegroundhovercolour', 'format_topcoll'),
                    'help' => 'settoggleforegroundhovercolour',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaulttgfghvrcolour)
                    )
                );
                $courseformatoptionsedit['toggleforegroundhoveropacity'] = array(
                    'label' => new lang_string('settoggleforegroundhoveropacity', 'format_topcoll'),
                    'help' => 'settoggleforegroundhoveropacity',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues)
                );
                $courseformatoptionsedit['togglebackgroundcolour'] = array(
                    'label' => new lang_string('settogglebackgroundcolour', 'format_topcoll'),
                    'help' => 'settogglebackgroundcolour',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaulttgbgcolour)
                    )
                );
                $courseformatoptionsedit['togglebackgroundopacity'] = array(
                    'label' => new lang_string('settogglebackgroundopacity', 'format_topcoll'),
                    'help' => 'settogglebackgroundopacity',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues)
                );
                $courseformatoptionsedit['togglebackgroundhovercolour'] = array(
                    'label' => new lang_string('settogglebackgroundhovercolour', 'format_topcoll'),
                    'help' => 'settogglebackgroundhovercolour',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'tccolourpopup',
                    'element_attributes' => array(
                        array('value' => $defaulttgbghvrcolour)
                    )
                );
                $courseformatoptionsedit['togglebackgroundhoveropacity'] = array(
                    'label' => new lang_string('settogglebackgroundhoveropacity', 'format_topcoll'),
                    'help' => 'settogglebackgroundhoveropacity',
                    'help_component' => 'format_topcoll',
                    'element_type' => 'select',
                    'element_attributes' => array($opacityvalues)
                );
            } else {
                $courseformatoptionsedit['toggleforegroundcolour'] = array(
                    'label' => $defaulttgfgcolour, 'element_type' => 'hidden');
                $courseformatoptionsedit['toggleforegroundopacity'] = array(
                    'label' => get_config('format_topcoll', 'defaulttgfgopacity'), 'element_type' => 'hidden');
                $courseformatoptionsedit['toggleforegroundhovercolour'] = array(
                    'label' => $defaulttgfghvrcolour, 'element_type' => 'hidden');
                $courseformatoptionsedit['toggleforegroundhoveopacity'] = array(
                    'label' => get_config('format_topcoll', 'defaulttgfghvropacity'), 'element_type' => 'hidden');
                $courseformatoptionsedit['togglebackgroundcolour'] = array(
                    'label' => $defaulttgbgcolour, 'element_type' => 'hidden');
                $courseformatoptionsedit['togglebackgroundopacity'] = array(
                    'label' => get_config('format_topcoll', 'defaulttgbgopacity'), 'element_type' => 'hidden');
                $courseformatoptionsedit['togglebackgroundhovercolour'] = array(
                    'label' => $defaulttgbghvrcolour, 'element_type' => 'hidden');
                $courseformatoptionsedit['togglebackgroundhoveopacity'] = array(
                    'label' => get_config('format_topcoll', 'defaulttgbghvropacity'), 'element_type' => 'hidden');
            }
            $readme = new moodle_url('/course/format/tabbedtopcoll/Readme.md');
            $readme = html_writer::link($readme, 'Readme.md', array('target' => '_blank'));
            $courseformatoptionsedit['readme'] = array(
                'label' => get_string('readme_title', 'format_topcoll'),
                'element_type' => 'static',
                'element_attributes' => array(get_string('readme_desc', 'format_topcoll', array('url' => $readme)))
            );
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Updates format options for a course
     *
     * In case if course format was changed to 'Collapsed Topics', we try to copy options
     * 'coursedisplay', 'numsections' and 'hiddensections' from the previous format.
     * If previous course format did not have 'numsections' option, we populate it with the
     * current number of sections.  The layout and colour defaults will come from 'course_format_options'.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        global $DB;
        /*
         * Notes: Using 'unset' to really ensure that the reset form elements never get into the database.
         *        This has to be done here so that the reset occurs after we have done updates such that the
         *        reset itself is not seen as an update.
         */
        $resetdisplayinstructions = false;
        $resetlayout = false;
        $resetcolour = false;
        $resettogglealignment = false;
        $resettoggleiconset = false;
        $resetalldisplayinstructions = false;
        $resetalllayout = false;
        $resetallcolour = false;
        $resetalltogglealignment = false;
        $resetalltoggleiconset = false;
        if (isset($data->resetdisplayinstructions) == true) {
            $resetdisplayinstructions = true;
            unset($data->resetdisplayinstructions);
        }
        if (isset($data->resetlayout) == true) {
            $resetlayout = true;
            unset($data->resetlayout);
        }
        if (isset($data->resetcolour) == true) {
            $resetcolour = true;
            unset($data->resetcolour);
        }
        if (isset($data->resettogglealignment) == true) {
            $resettogglealignment = true;
            unset($data->resettogglealignment);
        }
        if (isset($data->resettoggleiconset) == true) {
            $resettoggleiconset = true;
            unset($data->resettoggleiconset);
        }
        if (isset($data->resetalldisplayinstructions) == true) {
            $resetalldisplayinstructions = true;
            unset($data->resetalldisplayinstructions);
        }
        if (isset($data->resetalllayout) == true) {
            $resetalllayout = true;
            unset($data->resetalllayout);
        }
        if (isset($data->resetallcolour) == true) {
            $resetallcolour = true;
            unset($data->resetallcolour);
        }
        if (isset($data->resetalltogglealignment) == true) {
            $resetalltogglealignment = true;
            unset($data->resetalltogglealignment);
        }
        if (isset($data->resetalltoggleiconset) == true) {
            $resetalltoggleiconset = true;
            unset($data->resetalltoggleiconset);
        }

        $data = (array) $data;
        if ($oldcourse !== null) {
            $oldcourse = (array) $oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    } else if ($key === 'numsections') {
                        // If previous format does not have the field 'numsections'
                        // and $data['numsections'] is not set,
                        // we fill it with the maximum section number from the DB
                        $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                            WHERE course = ?', array($this->courseid));
                        if ($maxsection) {
                            // If there are no sections, or just default 0-section, 'numsections' will be set to default
                            $data['numsections'] = $maxsection;
                        }
                    }
                }
            }
        }

        $changes = $this->update_format_options($data);

        if ($changes && array_key_exists('numsections', $data)) {
            // If the numsections was decreased, try to completely delete the orphaned sections (unless they are not empty).
            $numsections = (int)$data['numsections'];
            $maxsection = $DB->get_field_sql('SELECT max(section) from {course_sections}
                        WHERE course = ?', array($this->courseid));
            for ($sectionnum = $maxsection; $sectionnum > $numsections; $sectionnum--) {
                if (!$this->delete_section($sectionnum, false)) {
                    break;
                }
            }
        }

        // Now we can do the reset.
        if (($resetalldisplayinstructions) ||
            ($resetalllayout) ||
            ($resetallcolour) ||
            ($resetalltogglealignment) ||
            ($resetalltoggleiconset)) {
            $this->reset_tabbedtopcoll_setting(0, $resetalldisplayinstructions, $resetalllayout, $resetallcolour,
                $resetalltogglealignment, $resetalltoggleiconset);
            $changes = true;
        } else if (($resetdisplayinstructions) ||
            ($resetlayout) ||
            ($resetcolour) ||
            ($resettogglealignment) ||
            ($resettoggleiconset)) {
            $this->reset_tabbedtopcoll_setting($this->courseid, $resetdisplayinstructions, $resetlayout, $resetcolour,
                $resettogglealignment, $resettoggleiconset);
            $changes = true;
        }

        return $changes;
    }

    public function section_action0($section, $action, $sr) {
        global $PAGE;

        // Topic based course.
        $tcsettings = $this->get_settings();
        if (($tcsettings['layoutstructure'] == 1) || ($tcsettings['layoutstructure'] == 4)) {
            if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
                // Format 'tabbedtopcoll' allows to set and remove markers in addition to common section actions.
                require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
                course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
                return null;
            }
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_tabbedtopcoll');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }
    public function section_action($section, $action, $sr) {
        global $PAGE;

        $tcsettings = $this->get_format_options();
        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'tabbedtopcoll' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        switch ($action) {
            case 'movetotabzero':
                return $this->move2tab(0, $section, $tcsettings);
                break;
            case 'movetotabone':
                return $this->move2tab(1, $section, $tcsettings);
                break;
            case 'movetotabtwo':
                return $this->move2tab(2, $section, $tcsettings);
                break;
            case 'movetotabthree':
                return $this->move2tab(3, $section, $tcsettings);
                break;
            case 'movetotabfour':
                return $this->move2tab(4, $section, $tcsettings);
                break;
            case 'movetotabfive':
                return $this->move2tab(5, $section, $tcsettings);
                break;
            case 'movetotabsix':
                return $this->move2tab(6, $section, $tcsettings);
                break;
            case 'movetotabseven':
                return $this->move2tab(7, $section, $tcsettings);
                break;
            case 'movetotabeight':
                return $this->move2tab(8, $section, $tcsettings);
                break;
            case 'movetotabnine':
                return $this->move2tab(9, $section, $tcsettings);
                break;
            case 'movetotabten':
                return $this->move2tab(10, $section, $tcsettings);
                break;
            case 'removefromtabs':
                return $this->removefromtabs($PAGE->course, $section, $tcsettings);
                break;
            case 'sectionzeroontop':
                return $this->sectionzeroswitch($tcsettings, true);
                break;
            case 'sectionzeroinline':
                return $this->sectionzeroswitch($tcsettings, false);
                break;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_tabbedtopcoll');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    // move section ID and section number to tab format settings of a given tab
    public function move2tab($tabnum, $section2move, $settings) {
        global $PAGE;
        global $DB;

        $course = $PAGE->course;

        // remove section number from all tab format settings
        $settings = $this->removefromtabs($course, $section2move, $settings);

        // add section number to new tab format settings if not tab0
        if($tabnum > 0){
            $settings['tab'.$tabnum] .= ($settings['tab'.$tabnum] === '' ? '' : ',').$section2move->id;
            $settings['tab'.$tabnum.'_sectionnums'] .= ($settings['tab'.$tabnum.'_sectionnums'] === '' ? '' : ',').$section2move->section;
            $this->update_course_format_options($settings);
        }
        return $settings;
    }

    // remove section id from all tab format settings
    public function removefromtabs($course, $section2remove, $settings) {
        global $CFG;
        global $DB;

//        $max_tabs = (isset($CFG->max_tabs) ? $CFG->max_tabs : 5);
        $max_tabs = 9;

        for($i = 0; $i <= $max_tabs; $i++) {
            if(strstr($settings['tab'.$i], $section2remove->id) > -1) {
                $sections = explode(',', $settings['tab'.$i]);
                $new_sections = array();
                foreach($sections as $section) {
                    if($section != $section2remove->id) {
                        $new_sections[] = $section;
                    }
                }
                $settings['tab'.$i] = implode(',', $new_sections);

                $section_nums = explode(',', $settings['tab'.$i.'_sectionnums']);
                $new_section_nums = array();
                foreach($section_nums as $section_num) {
                    if($section_num != $section2remove->section) {
                        $new_section_nums[] = $section_num;
                    }
                }
                $settings['tab'.$i.'_sectionnums'] = implode(',', $new_section_nums);
                $this->update_course_format_options($settings);
            }
        }
        return $settings;
    }

    // switch to show section0 always on top of the tabs
    public function sectionzeroswitch($settings, $value) {
        $settings['section0_ontop'] = $value;
        $this->update_course_format_options($settings);

        return $settings;
    }

    private function get_context() {
        global $SITE;

        if ($SITE->id == $this->courseid) {
            // Use the context of the page which should be the course category.
            global $PAGE;
            return $PAGE->context;
        } else {
            return context_course::instance($this->courseid);
        }
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_tabbedtopcoll_inplace_editable0($itemtype, $itemid, $newvalue) {
    global $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        global $DB;
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'tabbedtopcoll'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}
function format_tabbedtopcoll_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'tabbedtopcoll'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
    // deal with inplace changes of a tab name
    if ($itemtype === 'tabname') {
        global $DB, $PAGE;
        $courseid = key($_SESSION['USER']->currentcourseaccess);
        // the $itemid is actually the name of the record so use it to get the id

        // update the database with the new value given
        // Must call validate_context for either system, or course or course module context.
        // This will both check access and set current context.
        \external_api::validate_context(context_system::instance());
        // Check permission of the user to update this item.
//        require_capability('moodle/course:update', context_system::instance());
        // Clean input and update the record.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);
        $record = $DB->get_record('course_format_options', array('id' => $itemid), '*', MUST_EXIST);
        $DB->update_record('course_format_options', array('id' => $record->id, 'value' => $newvalue));

        // Prepare the element for the output ():
        $output = new \core\output\inplace_editable('format_tabbedtopcoll', 'tabname', $record->id,
            true,
            format_string($newvalue), $newvalue, 'Edit tab name',  'New value for ' . format_string($newvalue));

        return $output;
    }
}
