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
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('./view_attendance_report_form.php');
require_once('../../mod/attforblock/locallib.php');


require_login($course->id);
session_start();


$categoryid = optional_param('id', '-1', PARAM_INT);

$export = optional_param('export',false, PARAM_BOOL);

$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);



if($categoryid!=-1)
require_capability('block/custom_reports:getembaregreport', $context);

$navlinks[] = array('name' => get_string('emb_reg_report', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Registration Report', 'Registration Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

if($courses = get_courses($categoryid, '', 'c.id, c.fullname, c.startdate, c.idnumber, c.shortname') or $export){
	$mform = new mod_custom_reports_view_attendance_report_form('std_reg_report.php', array('courses'=>$courses, 'categoryid'=>$categoryid));
	if($fromform = $mform->get_data() or $export){
		$cselected = array();
		if($export){
			$export_courses = required_param('courses');
			$sessions_margin = array_reverse(explode(",", required_param('sessions')));

			//echo "select id, fullname, startdate, idnumber, shortname from {$CFG->prefix}courses where id IN ({$export_courses})";
			$courses = $DB->get_records_sql("select id, fullname, startdate, idnumber, shortname from {course} where id IN ({$export_courses})");

			$export_courses_sessions = "";
		}else{

			$export_courses = "";
		}
		$temp="";
		foreach($courses as $course){

			if((!$export AND $fromform->{'c'.$course->id}=='true') OR ($export)){
				//new code insert by khyam 1/8/2011
				if(!$ccontext = get_context_instance(CONTEXT_COURSE, $course->id)){
					print_error('badcontext');
				}
				//@khyam: exclude the users with hidden role assignment.
					
				$hidden_users = $DB->get_records_select("role_assignments", "contextid = '$ccontext->id'");
				$hidden_role_assignment = "";
				foreach ($hidden_users as $hidden_user)
				$hidden_role_assignment .= $hidden_user->userid.", "; //List all users with hidden assignments.
				$hidden_role_assignment = rtrim($hidden_role_assignment, ", ");
				$query = "SELECT u.id, u.firstname, u.lastname, u.idnumber from mdl_user u
                                                               JOIN {$CFG->prefix}role_assignments ra ON ra.userid=u.id
                                                               JOIN {$CFG->prefix}role r ON ra.roleid = r.id
                                                               JOIN {$CFG->prefix}context c ON ra.contextid = c.id
                                                               where r.name = 'Student' and
                                                               c.contextlevel = 50 and
                                                               c.instanceid = {$course->id}";
				if($hidden_role_assignment != "")
				//   $query .= " and u.id NOT IN ({$hidden_role_assignment})";
				$query .= " order by u.firstname";

				//$cselected = array("id" => array(), "name" => array(), "students" => array());
				$cselected["id"][] = $course->id;
				$cselected["name"][] = $course->fullname;
					
				$cselected["shortname"][] = $course->shortname;
				$cselected["idnumber"][] =  $course->idnumber;
				$cselected["students"][] =  $DB->get_records_sql($query);
				$cselected["startdate"][] = $course->startdate;
				if($export)
				$cselected["margin"][] = array_pop($sessions_margin);
				else
				$cselected["margin"][] = $fromform->{'session'.$course->id};

			}
			if($fromform->{'c'.$course->id}=='true' and !$export){
				$export_courses .= $course->id.",";
				$export_courses_sessions .= $fromform->{'session'.$course->id}.",";
			}

		}
			
		//$table->width = '80%';
		//$table->tablealign =  'center';
		//$table->cellpadding = '5px';
		$table = new html_table();
		$table->head = array();
		//$table->align = array();
		//$table->size = array();
			
		$table->head[] = 'S.No';
		$table->align[] = 'center';
		$table->size[] = '';
			
		$table->head[] = 'Registration No';
		$table->align[] = 'center';
		$table->size[] = '';
			
		$table->head[] = 'Name';
		$table->align[] = 'left';
		$table->size[] = '';
			
		//$students = array("idnumber" => array(), "name" => array());
			
		$user_sessions=array();
		$TotalSessions=0;
		for($i=0; $i<count($cselected["id"]); $i++){
			$temp_stu = array_slice($cselected["students"][$i],0,1);
			$temp_course = $DB->get_record('course', array('id'=> $cselected['id'][$i]));
			$temp_course->attendance_margin = $cselected['margin'][$i];
			//$max_sessions=get_maxgrade($temp_stu[0]->id,$temp_course);
			$courseCol = $cselected['name'][$i]."<br/>".$max_sessions;
			$table->head[] = $courseCol;
			$table->align[] = 'center';
			$table->size[] = '1px';


			foreach($cselected["students"][$i] as $student){
					
					
				//print_r($students["idnumber"]);
				if(!in_array($student->id, $students["userid"])){

					$students["userid"][] = $student->id;
					$students["idnumber"][] = $student->idnumber;
					$students["name"][] = $student->firstname.' '.$student->lastname;
				}
			}
		}
		

		//echo count($students["name"]);
		//print_r($students['idnumber']);
		//var_dump($students["name"]);
		$row_ite=0;
		for(; $row_ite<count($students["userid"]); $row_ite++){
			$table->data[$row_ite][] = $row_ite+1;
			$table->data[$row_ite][] = $students["idnumber"][$row_ite];
			$table->data[$row_ite][] = $students["name"][$row_ite];
			$all_sessions_missed=0;
			$all_sessions=0;
			for($j=0; $j<count($cselected['id']); $j++){
				$course = $DB->get_record('course', array('id'=> $cselected['id'][$j]));
					
				$course->attendance_margin = $cselected['margin'][$j];
				if(!key_exists($students["userid"][$row_ite], (array)$cselected["students"][$j])){
					$table->data[$row_ite][] = 'x';
					continue;
				}
				
				$table->data[$row_ite][] = 'Registered';

				//echo $students["userid"][$i]."--".$cselected['id'][$j]."<br>";
			}
				


		}
			
		//$x = (array)$cselected["students"][0];
		//echo $x[7926]->id;
		//echo key_exists(109882, $x);
		if($export){
			//print_table($table, true);
			//$category = get_record('course_categories', 'id', $categoryid);
			$table->category = $category->name;
			$table->duration = date("d M Y", $cselected["startdate"][0]).' to '.date("d M Y", time('now'));
			ExportToExcel($table);
		}
		else{
			//print_r($cselected["id"]);
			echo '<div style="text-align: center; font-weight: bold;">Students Registration Report </div>';
			echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">
							<form method="post" style="display: inline; margin: 0; padding: 0;">';
			echo 			'<input type="hidden" name="courses" value="'.rtrim($export_courses, ',').'" />';
			echo 			'<input type="hidden" name="sessions" value="'.rtrim($export_courses_sessions, ',').'" />';
			echo 			'<input type="hidden" name="id" value="'.$categoryid.'" />';
			echo 			'<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />
							</form>
							<span style="text-align: left; padding-left: 20px; text-decoration: underline;">Duration :- '.date("d M Y", $cselected["startdate"][0]).' to '.date("d M Y", time('now')).'</span></div>';

			if($categoryid!=-1)
			require_capability('block/custom_reports:getembaregreport', $context);

			echo html_writer::table($table);

		}
		exit();
	}else
	$mform->display();
}else{
	$OUTPUT->box_start('generalbox categorybox');
	print_whole_category_list2(NULL, NULL, NULL, -1, false);
	$OUTPUT->box_end();
}
echo $OUTPUT->footer();

//================Export to Excel================//
function ExportToExcel($data) {
	global $CFG;

	//require_once("$CFG->libdir/excellib.class.php");/*
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	$filename = "EMBA_students_reg_report:.xls";

	$workbook = new MoodleExcelWorkbook("-");
	/// Sending HTTP headers
	ob_clean();
	$workbook->send($filename);
	/// Creating the first worksheet
	$myxls =& $workbook->add_worksheet('EMBA_reg_report');
	/// format types
	$formatbc =& $workbook->add_format();
	$formatbc->set_bold(1);

	$header1 =& $workbook->add_format();
	$header1->set_bold(1);          // Make it bold
	$header1->set_align('center');  // Align text to center
	$header1->set_size(14);
	//$header1->set_fg_color(22);

	$header2 =& $workbook->add_format();
	$header2->set_bold(1);            // Make it bold
	$header2->set_align('center');  // Align text to center
	$header2->set_size(12);
	//$header2->set_fg_color(23);

	$normal =& $workbook->add_format();
	$normal->set_bold(0);
	$normal->set_align('center');
	$normal->set_size(10);

	$name =& $workbook->add_format();
	$name->set_bold(0);
	$name->set_size(10);

	$grey_code_f =& $workbook->add_format();
	$grey_code_f->set_bold(0);            // Make it bold
	$grey_code_f->set_size(12);
	$grey_code_f->set_fg_color(22);
	$grey_code_f->set_align('center');

	//$formatbc->set_size(14);
	$myxls->write(1, 0, "EMBA Students Registration Report",$header1);

	$myxls->write(4, 0, 'Duration');
	$myxls->write(4, 1, $data->duration, $formatbc);

	$i = 6;
	$j = 0;

	foreach ($data->head as $heading){
		$heading = str_replace('<br/>','',$heading);
		$heading = trim($heading);
		$myxls->write_string($i, $j, $heading,$header2);
		// $myxls->set_column($pos,$pos,(strlen($grade_item->get_name()))+4);
		$col_size = strlen($heading);
		$col_size+=6;

		if(preg_match('/^NAME/i',$heading)){
			$col_size=20;
		}
		$myxls->set_column($j,$j,$col_size);
		$j++;
	}
	$myxls->merge_cells(1,0,1,$j-1);
	$myxls->merge_cells(2,0,2,$j-1);
	$myxls->merge_cells(4,1,4,3);
	$myxls->set_row(1, 25 );
	$i = 7;
	$j = 0;
	foreach ($data->data as $row) {
		foreach ($row as $cell) {
			//$myxls->write($i, $j++, $cell);
			if (is_numeric($cell)) {
				//if($cell>25.99){
				//	$myxls->write_number($i, $j++, $cell,$grey_code_f);
				//}
				//else{
				$myxls->write_number($i, $j++, $cell,$normal);
				//}
			} else {
				if(preg_match('/^!!/',$cell)){
					$cell = str_replace("!!",'',$cell);
					$myxls->write_string($i, $j++, $cell,$grey_code_f);
				}
				else{
					if($j==2){
						$myxls->write_string($i, $j++, $cell,$name);
					}
					else{
						$myxls->write_string($i, $j++, $cell,$normal);
					}
				}
			}
		}
		$i++;
		$j = 0;
	}
	$workbook->close();
	exit;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
function print_whole_category_list2($category=NULL, $displaylist=NULL, $parentslist=NULL, $depth=-1, $showcourses = true) {
	global $CFG;

	// maxcategorydepth == 0 meant no limit
	if (!empty($CFG->maxcategorydepth) && $depth >= $CFG->maxcategorydepth) {
		return;
	}

	if (!$displaylist) {

		make_categories_list2($displaylist, $parentslist);
	}

	if ($category) {
		if ($category->visible or has_capability('moodle/category:viewhiddencategories', get_context_instance(CONTEXT_SYSTEM))) {
			print_category_info2($category, $depth, $showcourses);
		} else {
			return;  // Don't bother printing children of invisible categories
		}

	} else {

		$category->id = "0";
	}

	if ($categories = get_child_categories2($category->id)) {   // Print all the children recursively
		$countcats = count($categories);
		$count = 0;
		$first = true;
		$last = false;
		foreach ($categories as $cat) {
			$count++;
			if ($count == $countcats) {
				$last = true;
			}
			$up = $first ? false : true;
			$down = $last ? false : true;
			$first = false;

			print_whole_category_list2($cat, $displaylist, $parentslist, $depth + 1, $showcourses);
		}
	}
}
//////////////////////////////////////////////
function print_category_info2($category, $depth=0, $showcourses = false) {
	global $CFG, $DB, $OUTPUT;

	$strsummary = get_string('summary');

	$catlinkcss = null;
	if (!$category->visible) {
		$catlinkcss = array('class'=>'dimmed');
	}
	static $coursecount = null;
	if (null === $coursecount) {
		// only need to check this once
		$coursecount = $DB->count_records('course') <= FRONTPAGECOURSELIMIT;
	}

	if ($showcourses and $coursecount) {
		$catimage = '<img src="'.$OUTPUT->pix_url('i/course') . '" alt="" />';
	} else {
		$catimage = "&nbsp;";
	}

	$courses = get_courses($category->id, 'c.sortorder ASC', 'c.id,c.sortorder,c.visible,c.fullname,c.shortname,c.summary');
	if ($showcourses and $coursecount) {
		echo '<div class="categorylist clearfix '.$depth.'">';
		$cat = '';
		$cat .= html_writer::tag('div', $catimage, array('class'=>'image'));
		$catlink = html_writer::link(new moodle_url('', array('id'=>$category->id)), format_string($category->name), $catlinkcss);
		$cat .= html_writer::tag('div', $catlink, array('class'=>'name'));

		$html = '';
		if ($depth > 0) {
			for ($i=0; $i< $depth; $i++) {
				$html = html_writer::tag('div', $html . $cat, array('class'=>'indentation'));
				$cat = '';
			}
		} else {
			$html = $cat;
		}
		echo html_writer::tag('div', $html, array('class'=>'category'));
		echo html_writer::tag('div', '', array('class'=>'clearfloat'));

		// does the depth exceed maxcategorydepth
		// maxcategorydepth == 0 or unset meant no limit
		$limit = !(isset($CFG->maxcategorydepth) && ($depth >= $CFG->maxcategorydepth-1));
		if ($courses && ($limit || $CFG->maxcategorydepth == 0)) {
			foreach ($courses as $course) {
				$linkcss = null;
				if (!$course->visible) {
					$linkcss = array('class'=>'dimmed');
				}

				$courselink = html_writer::link(new moodle_url('/course/view.php', array('id'=>$course->id)), format_string($course->fullname), $linkcss);

				// print enrol info
				$courseicon = '';
				if ($icons = enrol_get_course_info_icons($course)) {
					foreach ($icons as $pix_icon) {
						$courseicon = $OUTPUT->render($pix_icon).' ';
					}
				}

				$coursecontent = html_writer::tag('div', $courseicon.$courselink, array('class'=>'name'));

				if ($course->summary) {
					$link = new moodle_url('/course/info.php?id='.$course->id);
					$actionlink = $OUTPUT->action_link($link, '<img alt="'.$strsummary.'" src="'.$OUTPUT->pix_url('i/info') . '" />',
					new popup_action('click', $link, 'courseinfo', array('height' => 400, 'width' => 500)),
					array('title'=>$strsummary));

					$coursecontent .= html_writer::tag('div', $actionlink, array('class'=>'info'));
				}

				$html = '';
				for ($i=0; $i <= $depth; $i++) {
					$html = html_writer::tag('div', $html . $coursecontent , array('class'=>'indentation'));
					$coursecontent = '';
				}
				echo html_writer::tag('div', $html, array('class'=>'course clearfloat'));
			}
		}
		echo '</div>';
	} else {
		echo '<div class="categorylist level'.$depth.'">';
		$html = '';
		$cat = html_writer::link(new moodle_url('', array('id'=>$category->id)), format_string($category->name), $catlinkcss);
		$cat .= html_writer::tag('span', ' ('.count($courses).')', array('title'=>get_string('numberofcourses'), 'class'=>'numberofcourse'));

		if ($depth > 0) {
			for ($i=0; $i< $depth; $i++) {
				//$html = html_writer::tag('div', $html .$cat, array('class'=>'indentation'));
				$html = html_writer::tag('div', $html .$cat, array('class'=>'indentation level'.$i ));
				$cat = '';
			}
		} else {
			$html = $cat;
		}

		echo html_writer::tag('div', $html, array('class'=>'category'));
		echo html_writer::tag('div', '', array('class'=>'clearfloat', 'style'=>'clear: both;'));
		echo '</div>';
	}
}

////////////
function make_categories_list2(&$list, &$parents, $requiredcapability = '',
$excludeid = 0, $category = NULL, $path = "") {

	// initialize the arrays if needed
	if (!is_array($list)) {
		$list = array();

	}
	if (!is_array($parents)) {
		$parents = array();

	}

	if (empty($category)) {
		// Start at the top level.
		$category = new stdClass;
		$category->id = 0;

	} else {

		// This is the excluded category, don't include it.
		if ($excludeid > 0 && $excludeid == $category->id) {

			return;
		}

		// Update $path.
		if ($path) {

			$path = $path.' / '.format_string($category->name);
		} else {

			$path = format_string($category->name);
		}

		// Add this category to $list, if the permissions check out.
		if (empty($requiredcapability)) {
			$list[$category->id] = $path;


		} else {
			ensure_context_subobj_present($category, CONTEXT_COURSECAT);
			$requiredcapability = (array)$requiredcapability;

			if (has_all_capabilities($requiredcapability, $category->context)) {

				$list[$category->id] = $path;
			}
		}
	}

	// Add all the children recursively, while updating the parents array.
	if ($categories = get_child_categories2($category->id)) {

		foreach ($categories as $cat) {
			if (!empty($category->id)) {
				if (isset($parents[$category->id])) {
					$parents[$cat->id]   = $parents[$category->id];
				}
				$parents[$cat->id][] = $category->id;
			}
			make_categories_list2($list, $parents, $requiredcapability, $excludeid, $cat, $path);
		}
	}
}



///////////////
function get_child_categories2($parentid) {

	static $allcategories = null;

	// only fill in this variable the first time
	if (null == $allcategories) {
		$allcategories = array();

		$categories = get_categories();

		foreach ($categories as $category) {
			if (empty($allcategories[$category->parent])) {

				$allcategories[$category->parent] = array();
			}

			$allcategories[$category->parent][] = $category;
		}
	}

	if (empty($allcategories[$parentid])) {

		return array();
	} else {

		return $allcategories[$parentid];
	}
}

?>
