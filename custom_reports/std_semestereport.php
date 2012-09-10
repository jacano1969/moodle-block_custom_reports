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
$categoryid = optional_param('id', '-1', PARAM_INT);
$export = optional_param('export',false, PARAM_BOOL);
$sort = optional_param('sort',false, PARAM_BOOL);
$sortby=$_POST[sortby];
$perd=$_POST[perod];
$sortorder=$_POST['sortorder'];
$cats=$_POST[catg];
if($cats){
	$_SESSION['cats'] =$cats;
}
$cats=$_SESSION['cats'];
if($categoryid==2){
	foreach ($cats as $cat)
	{
		$context = get_context_instance(CONTEXT_COURSECAT, $cat);
		require_capability('block/custom_reports:get_student_semester_report', $context);
	}
}
foreach ($cats as $cat)
{

	$courses1 = get_courses($cat, '', 'c.id, c.fullname, c.startdate,c.credithours, c.idnumber, c.shortname');
	$courses = array_merge((array)$courses1,(array)$courses);
}

$report=get_string('std_sem_report', 'block_custom_reports');
$navlinks[] = array('name' => get_string('std_sem_report', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Students Semester Report', 'Students Semester Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

if(($courses && $categoryid==2 ) or $export ){

	foreach($courses as $course){
		if($courses_==""){
			$courses_= $course->id;
		}
		else{
			$courses_.= ",".$course->id;
		}
	}



	////
	Print_form($courses_);

	if( isset($_POST['view'])or $export)
	{
		$user=$_POST['user'];

		if(isset($_POST['report']) or $export){
			$_SESSION['user'] =$user;

		}
		if($export){
			$user =$_SESSION['user'];
		}
		$uid_name = explode("|", $user);
		$uid = $uid_name[0];         // school id
		$uname = $uid_name[1];
		$uidnumber = $uid_name[2];
		$table = new html_table();
		$table->align[] = 'center';
		$table->size[] = '';
		$i=0;

		foreach ($cats as $cat)
		{
			$sql="SELECT cc.name as semester,cc.id,c.category, e.courseid as courseid, fullname,e.timecreated
				as timecreated FROM mdl_user_enrolments ue JOIN mdl_enrol e ON ( e.id = ue.enrolid ) JOIN mdl_course c 
				ON ( c.id = e.courseid ) JOIN mdl_course_categories cc ON(cc.id=c.category) AND ue.userid =$uid AND cc.id =$cat ";;
			$courses =  $DB->get_records_sql($sql);
			$sql1="SELECT name from mdl_course_categories  where id =$cat ";
			$semester =  $DB->get_record_sql($sql1);
			$table->data[$i][] ="<b>S/NO</b>";
			$table->data[$i][] ="<b>".$semester->name."</b>";
			$i++;

			foreach($courses as $course){
				$sn=1;
				$table->data[$i][] =$sn;
				$table->data[$i][] =$course->fullname;
				$sn++;
				$i++;
					
			}
			if(empty($courses)){
				$table->data[$i][]="";
				$table->data[$i][]="Not registered";
				$i++;

			}

		}
		//echo html_writer::table($table);
		if($export){
			ExportToExcel($table,$uname,$uidnumber);
		}
		else{

			echo '<form method="post" style="display: inline; margin: 0; padding: 0;">';
			echo 			'<input type="hidden" name="courses" value="'.rtrim($export_courses, ',').'" />';
			echo 			'<input type="hidden" name="id" value="'.$categoryid.'" />';
			echo 			'<input type="hidden" name="user" value="'.$user.'" />';

			echo 			'<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />';
			echo "<br/><b>Name:</b>".$uname;
			echo "<br/><b>Reg No:</b>".$uidnumber."<br/>";
			echo html_writer::table($table);
			echo			'</form></div>';


		}
	}
}else{
	$OUTPUT->box_start('generalbox categorybox');
	echo '<form method="post" action="std_semestereport.php?id=2" style="display: inline; margin: 0; padding: 0;">';
	print_whole_category_list2(NULL, NULL, NULL, -1, false);
	echo '<input type="submit" value="Select Users" />';
	echo '</form>';
	$OUTPUT->box_end();
}
echo $OUTPUT->footer();

//================Export to Excel================//
function ExportToExcel($data ,$uname,$uidnumber) {
	global $CFG,$USER;
	global $modules;
	//require_once("$CFG->libdir/excellib.class.php");/*
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	$filename = "Student Semester Record.xls";

	$workbook = new MoodleExcelWorkbook("-");
	/// Sending HTTP headers
	ob_clean();
	$workbook->send($filename);
	/// Creating the first worksheet
	$myxls =& $workbook->add_worksheet('Students');
	/// format types


	$normal =& $workbook->add_format();
	$normal->set_bold(0);            // Make it bold
	$normal->set_size(10);
	$normal->set_align('center');

	$formatbc =& $workbook->add_format();
	$formatbc->set_bold(1);
	$formatbc->set_align('center');
	$formatbc1 =& $workbook->add_format();

	$formatbc1->set_align('center');

	//$formatbc->set_size(14);
	$myxls->write(0, 3, "Student's Semester Record", $formatbc);
	$myxls->write(1, 3, "Name:".$uname, $formatbc);
	$myxls->write(2, 3, "Registration Number:".$uidnumber, $formatbc);


	$i = 4;
	$j = 0;
	$a=0;


	foreach ($data->data as $row) {
		foreach ($row as $cell) {

			//$myxls->write($i, $j++, $cell);
			if (is_numeric($cell)) {
				if(preg_match('/^<b>/',$cell)){
					$myxls->write_number($i, $j++, strip_tags($cell),$formatbc);
				}
				else{
					$myxls->write_number($i, $j++, strip_tags($cell),$formatbc1);
				}
			} else {
				if(preg_match('/^<b>/',$cell)){
					$myxls->write_string($i, $j++, strip_tags($cell),$formatbc);
				}
				else{
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
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
		if(count($courses)>0){
			$cat= '<input name="catg[]" type="checkbox" value="'.$category->id.'"/>'.$category->name;
			$cat .= html_writer::tag('span', ' ('.count($courses).')', array('title'=>get_string('numberofcourses'), 'class'=>'numberofcourse'));

		}
		else{
			$cat =$category->name; //html_writer::link(new moodle_url('', array('id'=>$category->id)), format_string($category->name), $catlinkcss);
			$cat .= html_writer::tag('span', ' ('.count($courses).')', array('title'=>get_string('numberofcourses'), 'class'=>'numberofcourse'));
		}
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

function Print_form($courses_) {
	global $CFG,$DB;
	$sql="SELECT u.id as id, username,firstname,lastname,idnumber
		FROM mdl_user u
		JOIN mdl_role_assignments ra ON ra.userid = u.id
		JOIN mdl_role r ON ra.roleid = r.id
		JOIN mdl_context c ON ra.contextid = c.id
		WHERE r.name = 'Student'
		AND c.contextlevel =50
		AND c.instanceid
		IN (
		
		$courses_
		)
		GROUP BY username";
		$users =  $DB->get_records_sql($sql);
		//echo $sql;
		echo "<div style='text-align: center; font-weight: bold;'>Student's Semester Report </div>";
			
		echo "<br/><br/><table   border='1'><tr><td>";
		echo "<label><b>Select User:</b></label></td><td>";

		echo "<form name='school_report' method='post' action='std_semestereport.php'>";
		echo 			'<input type="hidden" name="export" value="false" />';

		echo "<input type='hidden' name='id' id='id' value=2>";
		echo "<select name='user'>";
		foreach($users as $user){
			$value= $user->id."|".$user->firstname." ".$user->lastname."|".$user->idnumber;
			?>
<option value="<?php echo $value; ?>">
<?php echo  $user->firstname." ".$user->lastname; ?>
</option>
<?php
		}
		echo "</select></td>";
		?>
<td>&nbsp;</td>

<td><input type="submit" name="view" value="View"></td>
</form>
</tr>
</table>


		<?php

}


?>
