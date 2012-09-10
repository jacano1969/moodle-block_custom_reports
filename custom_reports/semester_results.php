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
 ?>
 <script type="text/javascript" src="jquery-1.3.2.js"></script>
<script type="text/javascript">
		$(document).ready(function() {
			
			//if(document.getElementById('verify').clicked == true){
			
				//alert("hina");
			//}
		});
		function getXMLHttp()
		{
		  var xmlHttp

		  try
		  {
		    //Firefox, Opera 8.0+, Safari
		    xmlHttp = new XMLHttpRequest();
		  }
		  catch(e)
		  {
		    //Internet Explorer
		    try
		    {
		      xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
		    }
		    catch(e)
		    {
		      try
		      {
		        xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
		      }
		      catch(e)
		      {
		        alert("Your browser does not support AJAX!")
		        return false;
		      }
		    }
		  }
		  return xmlHttp;
		}
		function verifyresult()
		{
			var xmlHttp = getXMLHttp();
			 
			  xmlHttp.onreadystatechange = function()
			  {
			    if(xmlHttp.readyState == 4)
			    {
			      HandleResponse(xmlHttp.responseText);
			    }
			  }

			  xmlHttp.open("GET", "VerifyGrades.php", true);
			  xmlHttp.send(null);
			//alert("hina");
			
		}
		function HandleResponse(response)
		{
		  document.getElementById('verified').innerHTML = "<b>Semester Result has been verfied.</b>";
		  document.getElementById("verify").disabled = true;
		}
		</script>



<?php

require_once('../../config.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('./view_attendance_report_form.php');
require_once('../../mod/attforblock/locallib.php');


require_login($course->id);
session_start();
$categoryid = optional_param('id', '-1', PARAM_INT);
if($categoryid!=-1){
	$semester=$DB->get_record_sql("select name,path from {course_categories} where id=$categoryid");
	$path = explode("/",      $semester->path);
	$semester = explode(" ", $semester->name);
	$semester = $semester[0];
	$path = $path[1];
	$school=$DB->get_record_sql("select name from {course_categories} where id=$path");
}
$export = optional_param('export',false, PARAM_BOOL);

$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);



if($categoryid!=-1)
require_capability('block/custom_reports:getresults', $context);

$navlinks[] = array('name' => get_string('results', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Semester Result', 'Semester Result', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

if($courses = get_courses($categoryid, '', 'c.id, c.fullname, c.startdate, c.idnumber, c.shortname') or $export){
	$mform = new mod_custom_reports_view_attendance_report_form('semester_results.php', array('courses'=>$courses, 'categoryid'=>$categoryid));
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

		$table = new html_table();
		//$table->head = array();

		$table->head[] = 'S.No';
		$table->align[] = 'center';
		$table->size[] = '40px';
		$table->headspan[] = 1;
		$table->head[] = 'Section';
		$table->align[] = 'center';
		$table->size[] = '50px';
		$table->headspan[] = 1;

		$table->head[] = 'Registration No';
		$table->align[] = 'center';
		$table->size[] = '220px';
		$table->headspan[] = 1;

		$table->head[] = "Subject=>";
		$table->align[] = 'center';
		$table->size[] = '150px';
		$table->headspan[] = 1;
		$user_sessions=array();
		$TotalSessions=0;
		$row=1;
		$column=4;
		for($i=0; $i<count($cselected["id"]); $i++){
			$temp_stu = array_slice($cselected["students"][$i],0,1);
			$temp_course = $DB->get_record('course', array('id'=> $cselected['id'][$i]));
			$temp_course->attendance_margin = $cselected['margin'][$i];

			$courseCol = $cselected['name'][$i]."<br/>".$max_sessions;

			$table->headspan[] = 2;
			$table->head[] = $courseCol;
			$table->align[] = 'center';
			$table->size[] = '1px';
			 
			if($i==0){
				$row1 = new html_table_row();
				$row2 = new html_table_row();
				$row3 = new html_table_row();
				$cell1 = new html_table_cell();
				$cell1->text = '';
				$cell1->colspan = 1;
				$cell2 = new html_table_cell();
				$cell2->text = '';
				$cell2->colspan = 1;
				$cell3 = new html_table_cell();
				$cell3->text = '';
				$cell3->colspan = 1;
				$cell4 = new html_table_cell();
				$cell4->text = '<b>Subject Code=></b>';
				$cell4->colspan = 1;
				$cell6 = new html_table_cell();
				$cell6->text = '<b>Credit Hours=></b>';
				$cell6->colspan = 1;
				$cell7 = new html_table_cell();
				$cell7->text = '<b>Name</b>';
				$cell7->colspan = 1;
				$row1->cells = array($cell1, $cell2,$cell3, $cell4);
				$row2->cells = array($cell1, $cell2,$cell3, $cell6);
				$row3->cells = array($cell1, $cell2,$cell3, $cell7);
			}

			$coursename = explode(" ", $temp_course->fullname);
			$coursecode = $coursename[0];
			 
			$cell5 = new html_table_cell();
			$cell5->text =  "<b>".$coursecode."<b>";
			$cell5->colspan = 2;
			$cell9 = new html_table_cell();
			$cell9->text =  "<b>".$temp_course->credithours."</b>";
			$cell9->colspan = 2;
			$cell10 = new html_table_cell();
			$cell10->text =  "<b>Grade</b>";
			$cell10->colspan = 1;
			$cell11 = new html_table_cell();
			$cell11->text =  "<b>G.P</b>";
			$cell11->colspan = 1;

			$row1->cells[] = $cell5;
			$row2->cells[] = $cell9;
			$row3->cells[] = $cell10;
			$row3->cells[] = $cell11;
			 
			foreach($cselected["students"][$i] as $student){
				//print_r($students["idnumber"]);
				if(!in_array($student->id, $students["userid"])){

					$students["userid"][] = $student->id;
					$students["idnumber"][] = $student->idnumber;
					$students["name"][] = $student->firstname.' '.$student->lastname;
				}
			}
		}
		$cell12 = new html_table_cell();
		$cell12->text =  "<b>Sem</b>";
		$cell12->colspan = 1;
		$cell13 = new html_table_cell();
		$cell13->text =  "<b>Cum</b>";
		$cell13->colspan = 1;
		$cell14 = new html_table_cell();
		$cell14->text =  "<b>Sem</b>";
		$cell14->colspan = 1;
		$cell15 = new html_table_cell();
		$cell15->text =  "<b>Cum</b>";
		$cell15->colspan = 1;
		$cell16 = new html_table_cell();
		$cell16->text =  "<b>Sem</b>";
		$cell16->colspan = 1;
		$cell17 = new html_table_cell();
		$cell17->text =  "<b>Cum</b>";
		$cell17->colspan = 1;
		$cell18 = new html_table_cell();
		$cell18->text =  "";
		$cell18->colspan = 1;
		$cell19 = new html_table_cell();
		$cell19->text =  "";
		$cell19->colspan = 2;
		$row1->cells[]=$cell19;
		$row1->cells[]=$cell19;
		$row1->cells[]=$cell19;
		$row1->cells[]=$cell18;

		$row2->cells[]=$cell19;
		$row2->cells[]=$cell19;
		$row2->cells[]=$cell19;
		$row2->cells[]=$cell18;

		$row3->cells[]=$cell12;
		$row3->cells[]=$cell13;
		$row3->cells[]=$cell14;
		$row3->cells[]=$cell15;
		$row3->cells[]=$cell16;
		$row3->cells[]=$cell17;
		$row3->cells[]=$cell18;
		$table->data = array($row1,$row2,$row3);

		$table->headspan[] = 2;
		$table->head[] = 'Credits';
		$table->align[]='center';
		$table->size[]='';
		/*$table->head[] = '';
		 $table->align[]='center';
		 $table->size[]='';*/
		$table->headspan[] = 2;
		$table->head[] = 'Grade Points';
		$table->align[]='center';
		$table->size[]='';
		/*$table->head[] = '';
		 $table->align[]='center';
		 $table->size[]='';*/
		$table->headspan[] = 2;
		$table->head[] = 'GPA';
		$table->align[]='center';
		$table->size[]='';
		/*$table->head[] = '';
		 $table->align[]='center';
		 $table->size[]=''; */
		$table->headspan[] = 2;
		$table->head[] = 'Status';
		$table->align[]='center';
		$table->size[]='';
		$table->headspan[] = 1;



		//echo count($students["name"]);
		//print_r($students['idnumber']);
		//var_dump($students["name"]);
		$row_ite=0;
		$row=5;
		for(; $row_ite<count($students["userid"]); $row_ite++){
			$table->data[$row][] = $row_ite+1;
			$table->data[$row][] = "";
			$table->data[$row][] = $students["idnumber"][$row_ite];
			$table->data[$row][] = $students["name"][$row_ite];
			$all_sessions_missed=0;
			$all_sessions=0;
			$GPA=0;
			$GP=0;
			$totalcredithours=0;
			for($j=0; $j<count($cselected['id']); $j++){
				$course = $DB->get_record('course', array('id'=> $cselected['id'][$j]));

				$course->attendance_margin = $cselected['margin'][$j];
				if(!key_exists($students["userid"][$row_ite], (array)$cselected["students"][$j])){
					$table->data[$row][] = '---';
					$table->data[$row][] = '---';
					continue;
				}
				$user_id=$students['userid'][$row_ite];
				$grade_item = $DB->get_record("grade_items", array('courseid'=>$course->id, 'itemtype'=>'course'));

				$sql="SELECT *
                    FROM mdl_grade_grades
                    WHERE itemid = (
                    SELECT id
                    FROM mdl_grade_items
                    WHERE courseid =$course->id
                    AND itemtype = 'course' )
                    AND userid =$user_id";
				// $credithours=$course->credithours;
				$credithours = explode("+",$course->credithours);
				$credithours= $credithours[0]+$credithours[1];
				$grade=$DB->get_record_sql($sql);
				// print_r($grade);
				$attendance = get_percent_absent($students["userid"][$row_ite], $course);
				$course_sess_att = get_grade($students["userid"][$row_ite],$course);
				$course_sessions = get_maxgrade($students["userid"][$row_ite],$course);
				$all_sessions+=$course_sessions;
				$course_sess_missed = $course_sessions - $course_sess_att;
				$all_sessions_missed+=$course_sess_missed;
				$subjgrade=grade_format_gradevalue_letter( $grade->finalgrade, $grade_item);
				if($grade){
					// $grade->finalgrade= number_format(($grade->finalgrade), 0);

					switch ($subjgrade) {
						case  ($subjgrade =='A'  ):

							$gradepoint=4.0;
							break;
						case  ($subjgrade =='B+'  ):

							$gradepoint=3.5;
							break;
						case  ($subjgrade =='B'  ):
							 
							$gradepoint=3.0;
							break;
						case  ($subjgrade =='C+'  ):
							$gradepoint=2.5;
							break;
						case  ($subjgrade =='C'  ):
							$gradepoint=2.0;
							break;
						case  ($subjgrade =='D'  ):
							$gradepoint=1.0;
							break;
						case  ($subjgrade =='F'  ):
							$gradepoint=0;
							break;
						case  ($subjgrade =='I'  ):
							$gradepoint=0;
							break;
					}
					$table->data[$row][] =$subjgrade ;
					$table->data[$row][] =$gradepoint*$credithours ;

				}else{
					$table->data[$row][] ="Not graded" ;
					$gradepoint=0;
					$table->data[$row][] =$gradepoint*$credithours ;
				}
				$length = strlen($course->fullname);
				//$characters = 1;
				$start = $length - 2;
				$section = substr($course->fullname , $start ,1);
				$table->data[$row][1] =$section;
				$GP=($gradepoint*$credithours)+$GP;
				$totalcredithours=$credithours+$totalcredithours;

				//echo $students["userid"][$i]."--".$cselected['id'][$j]."<br>";
			}
			//echo "user".$user_id." gpa ".$GPA." crd ".$totalcredithours."<br>";
			$GPA=$GP/$totalcredithours;
			$GPA= number_format(($GPA), 2);
			$table->data[$row][]=$totalcredithours;
			$table->data[$row][]="";
			$table->data[$row][]=number_format(($GP), 2);
			$table->data[$row][]="";
			$table->data[$row][]=$GPA;
			$table->data[$row][]="";
			$table->data[$row][]="";
			$row++;

		}

		//$x = (array)$cselected["students"][0];
		//echo $x[7926]->id;
		//echo key_exists(109882, $x);
		if($export){
			//print_table($table, true);
			//$category = get_record('course_categories', 'id', $categoryid);
			$table->category = $category->name;
			$table->schoolname = $school->name;
			$table->semester = $semester;
			$durationend = strtotime("+$temp_course->numsections weeks", $temp_course->timemodified);
			$table->end_duration  =date(" M jS, Y", $durationend);
			$table->start_duration=date(" M jS, Y", $temp_course->timemodified);
			//$table->duration = date("d M Y", $cselected["startdate"][0]).' to '.date("d M Y", time('now'));
			ExportToExcel($table);
		}
		else{
		/*foreach($cselected["id"] as  $courseid){
		 	$sql="select verified from mdl_grade_items where courseid=$courseid and itemname='Grades'";
		 	//echo $sql;
		 	$verification=$DB->get_record_sql($sql);
		 	if(isset($verification->verified))
		 	$grades_verification[]=$verification->verified;
		 }*/

		 //print_r($cselected["id"]);

		 echo '<div style="text-align: center; font-weight: bold;"><u>'.$school->name.'</u><br></div>';
		 echo '<div style="text-align: center; font-weight: bold;"><u>On Campus Semester Result Information</u><br></div>';
		 $Duration_end = strtotime("+$temp_course->numsections weeks", $temp_course->timemodified);
		 echo '<div style="text-align: center; font-weight: bold;"><br/>Semester: '.$semester.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Duration: '.date(" M jS, Y", $temp_course->timemodified).'-'. date(" M jS, Y", $Duration_end).'</div>';
		 echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">
                            <form method="post" style="display: inline; margin: 0; padding: 0;">';
		 echo             '<input type="hidden" name="courses" value="'.rtrim($export_courses, ',').'" />';
		 echo             '<input type="hidden" name="sessions" value="'.rtrim($export_courses_sessions, ',').'" />';
		 echo             '<input type="hidden" name="id" value="'.$categoryid.'" />';
		 echo             '<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />
                           </form>';
		 echo '<form method="post" style="display: inline; margin: 0; padding: 0;">';
	?>
		 <?php

		 // if($categoryid!=-1)
		 //       require_capability('block/custom_reports:getattendancereport', $context);
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
	$filename = "Semester_Result:.xls";
	 
	$workbook = new MoodleExcelWorkbook("-");
	/// Sending HTTP headers
	ob_clean();
	$workbook->send($filename);
	/// Creating the first worksheet
	$myxls =& $workbook->add_worksheet('Semester_result');
	/// format types
	$formatbc =& $workbook->add_format();
	$formatbc->set_bold(1);
	$myxls->set_row(1, 20 );
	$myxls->set_row(2, 20 );
	//	$myxls->set_column(2,3,30);
	$header1 =& $workbook->add_format();
	$header1->set_bold(1);          // Make it bold
	$header1->set_align('center');  // Align text to center
	$header1->set_size(14);
	//$header1->set_fg_color(22);

	$header2 =& $workbook->add_format();
	$header2->set_bold(1);            // Make it bold
	$header2->set_align('center');  // Align text to center
	$header2->set_size(12);
	$header2->set_text_wrap(1);

	$normal =& $workbook->add_format();
	$normal->set_bold(0);
	$normal->set_align('center');
	$normal->set_size(10);
	$normal1 =& $workbook->add_format();
	$normal1->set_bold(1);
	$normal1->set_align('center');
	$normal1->set_size(10);

	$name =& $workbook->add_format();
	$name->set_bold(0);
	$name->set_size(10);
	$name->set_align('center');


	$grey_code_f =& $workbook->add_format();
	$grey_code_f->set_bold(0);            // Make it bold
	$grey_code_f->set_size(12);
	$grey_code_f->set_fg_color(22);
	$grey_code_f->set_align('center');

	//$formatbc->set_size(14);
	$myxls->write(1, 0, $data->schoolname,$header1);
	$myxls->write(2, 0, "On Campus Semester Result Information",$header1);
	$myxls->write(3, 1, "Semester:".$data->semester,$header2);
	$myxls->write(3, 4, "Duration:".$data->start_duration."-".$data->end_duration,$header2);

	$myxls->set_column(0,0,8);
	$myxls->set_column(1,1,10);
	$myxls->set_column(3,3,30);
	$myxls->set_column(2,2,20);
	$myxls->merge_cells(3,1,3,2);
	$myxls->merge_cells(3,4,3,10);
	$i = 5;
	$j = 0;
	$a=0;
	$flag1=false;
	foreach ($data->head as $heading){
		$heading = str_replace('<br/>','',$heading);
		$heading = trim($heading);

		if($heading=="Credits"){
			$flag1=true;
		}
		if($flag1==true){
			$myxls->set_column($j,$j,5);
		}

		if($j>=4 &&  $flag1==false){
			$myxls->set_column($j,$j,8);
			//$myxls->merge_cells($i,$j,$i,$j+1);/////merge
		}
		//	if($data->headspan[$a]==2){
		//$myxls->set_column($j,$j+1,38);
		//$myxls->merge_cells($i,$j,$i,$j+1);/////merge
		//	}
		$myxls->write_string($i, $j, $heading,$header2);
		//$myxls->set_column($pos,$pos,(strlen($grade_item->get_name()))+4);
		$col_size = strlen($heading);
		$col_size+=6;

		//	if(preg_match('/^NAME/i',$heading)){
		//		$col_size=39;
		//	}
		$myxls->set_row(1, 15 );
		//	$myxls->set_column($j,$j,$col_size);
		if($data->headspan[$a]==2){
			$myxls->merge_cells($i,$j,$i,$j+1);
			$j+=2;
		}
		if($data->headspan[$a]==1){
			$j++;
		}
		//$j++;
		$a++;
	}
	$myxls->set_row(5, 55 );
	$myxls->merge_cells(1,0,1,$j-1);
	$myxls->merge_cells(2,0,2,$j-1);
	$myxls->merge_cells(4,1,4,3);

	$i = 6;
	$j = 0;
	foreach ($data->data as $row) {
		foreach ($row as $cell) {
			foreach ($cell as $cel) {
				if($cel->colspan==2){
					//$myxls->merge_cells($i,$j,$i,$j+1);/////merge
				}
				if (is_numeric($cel)) {
					$myxls->write_string($i, $j, strip_tags($cel->text),$normal1);
				}
				else{
					$myxls->write_string($i, $j, strip_tags($cel->text),$normal1);
				}
				if($cel->colspan==2){
					$myxls->merge_cells($i,$j,$i,$j+1);
					$j+=2;
				}
				if($cel->colspan==1){
					$j++;
				}
			}
		}
		$i++;
		$j = 0;
	}
	$i=9;
	$j=0;
	$flag=false;
	foreach ($data->data as $row) {
		foreach ($row as $cell) {
			if ($i>=9 && $flag==true) {
				if (is_numeric($cell)) {
					$myxls->write_number($i, $j++, $cell,$normal);
				}
				else{
					$myxls->write_string($i, $j++, $cell,$normal);
				}
			}
		}
		$i++;
		$j = 0;
		if ($i==12 && $flag==false) {
			$i=9;
			$flag=true;
		}
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
		//Added By Hina Yousuf to show only those categories in which the user has access

		$context = get_context_instance(CONTEXT_COURSECAT, $category->id);
		if(  has_capability('block/custom_reports:getresults', $context)){
			echo  html_writer::tag('div', $html, array('class'=>'category'));
		}
		//end
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
