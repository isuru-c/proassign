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
 * This file keeps track of upgrades to the proassign module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_proassign
 * @copyright  2015 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute proassign upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_proassign_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one. Please, delete
     * this comment lines once this file start handling proper
     * upgrade code.
     *
     * if ($oldversion < YYYYMMDD00) { //New version in version.php
     * }
     *
     * Lines below (this included)  MUST BE DELETED once you get the first version
     * of your module ready to be installed. They are here only
     * for demonstrative purposes and to show how the proassign
     * iself has been upgraded.
     *
     * For each upgrade block, the file proassign/version.php
     * needs to be updated . Such change allows Moodle to know
     * that this file has to be processed.
     *
     * To know more about how to write correct DB upgrade scripts it's
     * highly recommended to read information available at:
     *   http://docs.moodle.org/en/Development:XMLDB_Documentation
     * and to play with the XMLDB Editor (in the admin menu) and its
     * PHP generation posibilities.
     *
     * First example, some fields were added to install.xml on 2007/04/01
     */
    if ($oldversion < 2007040100) {

        // Define field course to be added to proassign.
        $table = new xmldb_table('proassign');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Add field course.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field intro to be added to proassign.
        $table = new xmldb_table('proassign');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'name');

        // Add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to proassign.
        $table = new xmldb_table('proassign');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Once we reach this point, we can store the new version and consider the module
        // ... upgraded to the version 2007040100 so the next time this block is skipped.
        upgrade_mod_savepoint(true, 2007040100, 'proassign');
    }

    // Second example, some hours later, the same day 2007/04/01
    // ... two more fields and one index were added to install.xml (note the micro increment
    // ... "01" in the last two digits of the version).
    if ($oldversion < 2007040101) {

        // Define field timecreated to be added to proassign.
        $table = new xmldb_table('proassign');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'introformat');

        // Add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to proassign.
        $table = new xmldb_table('proassign');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'timecreated');

        // Add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course (not unique) to be added to proassign.
        $table = new xmldb_table('proassign');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Add index to course field.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Another save point reached.
        upgrade_mod_savepoint(true, 2007040101, 'proassign');
    }

    // Third example, the next day, 2007/04/02 (with the trailing 00),
    // some actions were performed to install.php related with the module.
    if ($oldversion < 2007040200) {

        // Insert code here to perform some actions (same as in install.php).

        upgrade_mod_savepoint(true, 2007040200, 'proassign');
    }

    if($oldversion < 2016052700){
		
		$table = new xmldb_table('proassign');
		$field = new xmldb_field( 'startdate', XMLDB_TYPE_INTEGER, '10'
                , XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'introformat' );
		
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign');
		$field = new xmldb_field( 'duedate', XMLDB_TYPE_INTEGER, '10'
                , XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'startdate' );
		
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		upgrade_mod_savepoint(true, 2016052700, 'proassign');
	}
	
	if($oldversion < 2016060100){
				
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('assignment', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'assignment');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }		
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('status', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'timemodified');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'status');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_submission');
		$field = new xmldb_field('latest', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'attemptnumber');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		upgrade_mod_savepoint(true, 2016060100, 'proassign');
	}
	
	if($oldversion < 2016060122){
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('assignment', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'assignment');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('grader', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timemodified');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('grade', XMLDB_TYPE_DECIMAL, '(10,5)', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grader');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$table = new xmldb_table('proassign_grades');
		$field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'grade');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
				
		upgrade_mod_savepoint(true, 2016060122, 'proassign');
	}
		
	if($oldversion < 2016060300){
		
		$table = new xmldb_table('proassign');
		$field = new xmldb_field('use1', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('input1', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'use1');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('output1', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'input1');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('visible1', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'output1');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('mark1', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'visible1');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$field = new xmldb_field('use2', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'mark1');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('input2', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'use2');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('output2', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'input2');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('visible2', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'output2');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('mark2', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'visible2');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		$field = new xmldb_field('use3', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'mark2');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('input3', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'use3');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('output3', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'input3');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('visible3', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'output3');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		$field = new xmldb_field('mark3', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'visible3');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		upgrade_mod_savepoint(true, 2016060300, 'proassign');
	}
	
    return true;
}
