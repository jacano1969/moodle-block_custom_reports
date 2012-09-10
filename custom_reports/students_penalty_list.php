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
 * @package   block-custom_reports
 * @copyright 2012 Hina Yousuf
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once("../../mod/feedback/lib.php");
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('./view_attendance_report_form.php');
require_once('../../mod/attforblock/locallib.php');
require('../../mod/attforblock/tcpdf/config/lang/eng.php');
require('../../mod/attforblock/tcpdf/tcpdf.php');
include('dbcon.php');?>

<?php
require_login($course->id);
session_start();

$export = optional_param('export',false, PARAM_BOOL);

$navlinks[] = array('name' => get_string('students_penalty', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Students Penalty List', 'Students Penalty List', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);
if(isset($_POST['report']) or $export)
{
	$currentSemester=strtotime("-6 months", time());
	$school=$_POST['school_'];
	if(isset($_POST['report'])){

		$_SESSION['school'] =$school;
	}
	if($export){

		$school =$_SESSION['school'];
	}
	$id_name = explode("|", $school);
	$id = $id_name[0];         // school id
	$name = $id_name[1];

	$context = get_context_instance(CONTEXT_COURSECAT, $id);
	require_capability('block/custom_reports:getstudentsList', $context);

	echo "<br/>";
	echo '<div style="text-align: center; font-weight: bold;">STUDENTS PENALTY LIST <br></div>';
	echo '<div style="text-align: center; font-weight: bold;">SCHOOL:&nbsp;'.$name .'<br></div>';
	echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">
						<form method="post" style="display: inline; margin: 0; padding: 0;">';


	echo             '<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />
						</form>';                
	echo "<br/>";
	$headings=array('Registration Number','Name','Course');
	$feedback_no=$_POST['feedback_no'];
	if(isset($_POST['report'])){
		$_SESSION['feedback_no']=$feedback_no;
	}

	if($export){
		$feedback_no=$_SESSION['feedback_no'];
	}
	$no_of_departments=0;
	$table = new html_table();
	$table->head = array();

	$table->head[] =  'Registration Number';
	$table->align[] = 'center';
	$table->size[] = '';

	$table->head[] =  'Name';
	$table->align[] = 'center';
	$table->size[] = '';

	$table->head[] =  'Course';
	$table->align[] = 'center';
	$table->size[] = '';

	$j=0;

	$sql="SELECT u.id, username,firstname,lastname,idnumber
		FROM mdl_user u
		JOIN mdl_role_assignments ra ON ra.userid = u.id
		JOIN mdl_role r ON ra.roleid = r.id
		JOIN mdl_context c ON ra.contextid = c.id
		WHERE r.name = 'Student'
		AND c.contextlevel =50
		AND c.instanceid
		IN (	
		SELECT id
		FROM mdl_course
		WHERE category
		IN (
		
		SELECT id
		FROM mdl_course_categories
		WHERE path LIKE '/$id%'
		)
		)
		GROUP BY username";
	$users =  $DB->get_records_sql($sql);

	foreach($users as $user){
		$user_courses="";
		$sql="SELECT e.courseid as courseid, fullname,e.timecreated as timecreated
						FROM mdl_user_enrolments ue
						JOIN mdl_enrol e ON ( e.id = ue.enrolid )
						JOIN mdl_course c ON ( c.id = e.courseid )
						AND ue.userid =$user->id AND c.startdate>$currentSemester";
		$courses =  $DB->get_records_sql($sql);
		foreach($courses as $course){
			$context = get_context_instance(CONTEXT_COURSE, $course->courseid, MUST_EXIST);
			$sql=" SELECT ra.userid FROM mdl_role_assignments ra WHERE ra.roleid =5 AND ra.userid=$user->id and contextid =$context->id ";

			if($student=$DB->get_records_sql($sql)){
				$feedbacks	 =$DB->get_records_sql("SELECT id,name from {feedback} f WHERE course =$course->courseid and name like'$feedback_no%'");
					
				//			if(isset($feedback->id)){
				foreach($feedbacks as $feedback){
					$userfeedback=$DB->get_record_sql("SELECT * FROM {feedback_completed} where feedback = $feedback->id and userid=$user->id");
					if(!isset($userfeedback->id)){

						if($user_courses==""){
							$user_courses.=$course->fullname;
						}
						else{
							$user_courses.=" , ".$course->fullname;
						}

					}


				}
			}
		}
		if ($user_courses!=""){
				
			$table->data[$j][]=$user->idnumber;
			$table->data[$j][]=$user->firstname." ".$user->lastname;
			$table->data[$j][]=$user_courses;
			$j++;
		}

	}
}
else{
	echo '<form name="myform" action="students_penalty_list.php" method="POST">';
	echo "<div align='center'><h1>Students Penalty List</h1></div>";
	echo "<b>Select Feedback Type:</b>";
	echo "<select name='feedback_no'>";
	echo '<option value="First Student Feedback">First Student Feedback </option>';
	echo '<option value="2nd Student Feedback">Second Student Feedback </option>';
	echo '</select>';
	echo "<br/><b>Select School:</b>";
	$query = "SELECT id,name FROM {course_categories} WHERE parent =0";
	if($schools = $DB->get_records_sql($query)){
		echo "<select name='school_' id='school_'>";
		foreach($schools as $school){
			$value= $school->id."|".$school->name;
			?>
<option value="<?php echo $value; ?>">
<?php echo $school->name ?>
</option>
<?php
		}
		echo "</select><br/>";
	}
	echo '<br/><input type="submit" value="View Report" name="report">';
	$OUTPUT->box_start('generalbox categorybox');
	echo '</form>';
}

if($export   ){
	ExportToExcel($table);
}
if(isset($_POST['report'])){
	echo html_writer::table($table);
}
$OUTPUT->box_end();
echo $OUTPUT->footer();

//================Export to Excel================//
function ExportToExcel($data,$name,$type) {
	global $CFG;
	global $headings;
	global $name,$type;

	//require_once("$CFG->libdir/excellib.class.php");/*
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	$filename = "Students_Penalty_List.xls";

	$workbook = new MoodleExcelWorkbook("-");
	/// Sending HTTP headers
	ob_clean();
	$workbook->send($filename);
	/// Creating the first worksheet
	$myxls =& $workbook->add_worksheet('Students Penalty List');
	/// format types
	$formatbc =& $workbook->add_format();
	$formatbc1 =& $workbook->add_format();
	$formatbc->set_bold(1);
	$myxls->set_column(0, 0, 50);
	$myxls->set_column(1, 7, 50);
	$formatbc->set_align('center') ;
	$formatbc1->set_align('center') ;
	$xlsFormats = new stdClass();
	$xlsFormats->default = $workbook->add_format(array(
                            'width'=>40));
	//$formatbc->set_size(14);
	$myxls->write(0, 1, "Students Penalty List", $formatbc);
	$myxls->write(1, 1, $name, $formatbc);



	foreach ($headings as $heading)
	$myxls->write_string(4, $j++, strtoupper($heading), $formatbc);

	$i = 5;
	$j = 0;
	foreach ($data->data as $row) {
		foreach ($row as $cell) {

			if (is_numeric($cell)) {
				if(strstr($cell, "<b>") ==true){
					$myxls->write_number($i, $j++, strip_tags($cell),$formatbc);
				}else{
					$myxls->write_number($i, $j++, strip_tags($cell),$formatbc1);
				}
			} else {
				if(strstr($cell, "<b>") ==true){
					$myxls->write_string($i, $j++, strip_tags($cell),$formatbc);
				}else{
					$myxls->write_string($i, $j++, strip_tags($cell),$formatbc1);
				}

			}
		}
		$i++;
		$j = 0;
	}
	$workbook->close();
	exit;
}



?>
