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
 * @copyright  &copy; 2009-onwards G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Topics_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
$string['pluginname'] = 'Collapsed Tabbed Topics';
$string['section0name'] = 'General';

$string['currentsection'] = 'This topic';
$string['editsection'] = 'Edit topic';
$string['deletesection'] = 'Delete topic';
$string['sectionname'] = 'Topic';
$string['section0name'] = 'General';
$string['hidefromothers'] = 'Hide topic';
$string['showfromothers'] = 'Show topic';
$string['addsections'] = 'Add Topics';

// tab related strings
$string['modulecontent'] = 'Module Content';

$string['section0_label'] = 'Show Topic 0 above all tabs';
$string['section0_help'] = 'When checked topic 0 is always shown above the tabs.';

$string['single_section_tabs'] = 'Use topic name as tab name for single topics';
$string['single_section_tabs_label'] = 'Use topic name as tab name for single topics';
$string['single_section_tabs_help'] = 'When checked tabs with a single topic will use the topic name as tab name.';

$string['tabname'] = 'Tab';
$string['tabzero_title'] = 'Module Content';
$string['tabtitle_edithint'] = 'Edit tab name';
$string['tabtitle_editlabel'] = 'New value for {a}';

$string['hidden_tab_hint'] = 'This tab contains only hidden topics and will not be shown to students';