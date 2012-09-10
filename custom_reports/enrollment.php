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
require('../../mod/attforblock/tcpdf/config/lang/eng.php');
require('../../mod/attforblock/tcpdf/tcpdf.php');
include('dbcon.php');

require_login($course->id);
session_start();


$categoryid = optional_param('id', '-1', PARAM_INT);
$type = optional_param('type', '-1', PARAM_INT);

$export = optional_param('export',false, PARAM_BOOL);


$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);

//if($categoryid!=-1)
//require_capability('block/custom_reports:getattendancereport', $context);
$report=get_string('attendance_custom_reports', 'block_custom_reports');
$navlinks[] = array('name' => get_string('attendance_custom_reports', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Attendance Custom Report', 'Attendance Custom Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

if($courses = get_courses($categoryid, '', 'c.id, c.fullname, c.startdate, c.idnumber, c.shortname') or $export or $perd){
	$mform = new mod_custom_reports_view_attendance_report_form('enrollment.php', array('courses'=>$courses, 'categoryid'=>$categoryid,'report'=>$report));
	if($fromform = $mform->get_data() or $export ){
		$cselected = array();
		if($export ){

			$export_courses = required_param('courses');

			

			echo "select id, fullname, startdate, idnumber, shortname from courses where id IN ({$export_courses})";
			$courses = $DB->get_records_sql("select id, fullname, idnumber, shortname from {course} where id IN ({$export_courses})");
			//print_r($courses);
			$export_courses_sessions = "";
		}else{

			$export_courses = "";
		}
		$temp="";

		//if($type==2 || $type==3){

			$table = new html_table();
			$table->head = array();

			
		//}

		$i=0;
		foreach($courses as $course){

			if((!$export AND $fromform->{'c'.$course->id}=='true') OR ($export )){
				//new code insert by khyam 1/8/2011
				
				//@khyam: exclude the users with hidden role assignment.

			
				$query1 = "SELECT *
FROM mdl_user AS u, mdl_log AS l
WHERE u.id = l.enrolleduser
AND course =$course->id and (action='enrol' or action='unenrol')";
				$students =  $DB->get_records_sql($query1);
				

				//$cselected = array("id" => array(), "name" => array(), "students" => array());
			

				//if($type==2  ||  $type==3){
				//if(!empty($students)){
					$table->head[] = $course->fullname;
					$table->align[] = 'center';
					$table->size[] = '40px';
					$table->headspan[] = 4;
					$row=0;
					$table->data[$row][]="Fullname";
					$table->data[$row][]="Reg: No";
					$table->data[$row][]="Time";
					$table->data[$row][]="Action";
					
				//}
					/*if(!$export){
						$table->data[$row][]="Template";
						}*/

				
			
				//if($type==2 || $type==3){

					//
					$row=1;
					$flag=false;
					foreach($students as $student){

						

						//$attendance = get_percent_absent($student->id, $course,$where);

					//	if($type==3){
						//	if($student->firstname !=""){

								for ($k=0;$k<$i;$k++){
									if( $table->data[$row][$k]==""){
										$table->data[$row][$k]="---";
										$table->data[$row][$k+1]="---";
										$table->data[$row][$k+2]="---";
										$table->data[$row][$k+3]="---";
										

									}
								}
								
								//$warning[$student->id][]=$course->fullname."|".$student->firstname." ".$student->lastname."|".$attendance."%"."|".$course->startdate."|".$student->idnumber."|".$student->address."|".$student->phone2."|".$student->fathername;
								$table->data[$row][$i]=  $student->firstname.' '.$student->lastname;
								$table->data[$row][$i+1]=$student->idnumber;
								$table->data[$row][$i+2]=date("d M Y", $student->time);
								$table->data[$row][$i+3]=$student->action;
								
								$row++;

								//}


							/*}
							else{
								$table->data[$row][$i]  =",,,";//$student->idnumber."<br/>".$student->firstname.' '.$student->lastname."<br/>".$attendance."%";
								$table->data[$row][$i+1]=",,,";
								$table->data[$row][$i+2]=",,,";
								
								//$row++;
							}*/

						//}
						

						//$row++;
						// }
					}
			//	}

				////
				$i+=4;
			}

			//}
			if($fromform->{'c'.$course->id}=='true' and !$export){
				$export_courses .= $course->id.",";
				
			}

			
				


		}
		//for warning letter templates
		

		///



		if($export){

			$table->category = $category->name;
			//$table->duration = date("d M Y", $cselected["startdate"][0]).' to '.date("d M Y", time('now'));
			ExportToExcel($table);
		}
		else{

			echo '<div style="text-align: center; font-weight: bold;">ATTENDANCE SUMMARY <br>ABSENTEES RECORDS (Percentage)</div>';
			echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">';
			
			echo			'<form method="post" style="display: inline; margin: 0; padding: 0;">';
			echo 			'<input type="hidden" name="courses" value="'.rtrim($export_courses, ',').'" />';
			echo 			'<input type="hidden" name="sessions" value="'.rtrim($export_courses_sessions, ',').'" />';
			echo 			'<input type="hidden" name="id" value="'.$categoryid.'" />';
			echo 			'<input type="hidden" name="type" value="'.$type.'" />';
			echo 			'<input type="hidden" name="start" value="'.$start.'" />';
			echo 			'<input type="hidden" name="end" value="'.$end.'" />';
			echo 			'<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />
							</form>';


			////
			


			///

			if($categoryid!=-1)
			require_capability('block/custom_reports:getattendancereport', $context);

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
	global $reportname;
	global $start;
	global $end;

	//require_once("$CFG->libdir/excellib.class.php");/*
	require_once($CFG->dirroot.'/lib/excellib.class.php');
	$filename = "Attendance_report:.xls";

	$workbook = new MoodleExcelWorkbook("-");
	/// Sending HTTP headers
	ob_clean();
	$workbook->send($filename);
	/// Creating the first worksheet
	$myxls =& $workbook->add_worksheet('Attendances');
	/// format types
	$formatbc =& $workbook->add_format();
	$formatbc->set_bold(1);
	$myxls->set_row(1, 20 );//Added By Hina
	$myxls->set_row(2, 20 );//Added By Hina

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

	$green =& $workbook->add_format();
	$green->set_bold(0);            // Make it bold
	$green->set_size(12);
	$green->set_fg_color(3);
	$green->set_align('center');


	$red =& $workbook->add_format();
	$red->set_bold(0);            // Make it bold
	$red->set_size(12);
	$red->set_fg_color(10);
	$red->set_align('center');

	$orange =& $workbook->add_format();
	$orange->set_bold(0);            // Make it bold
	$orange->set_size(12);
	$orange->set_fg_color('orange');
	$orange->set_align('center');

	$normal =& $workbook->add_format();
	$normal->set_bold(0);
	$normal->set_align('center');
	$normal->set_size(10);

	//Added By Hina Yousuf//
	$normal1 =& $workbook->add_format();
	$normal1->set_bold(1);
	$normal1->set_align('center');
	$normal1->set_size(10);
	//end

	$name =& $workbook->add_format();
	$name->set_bold(0);
	$name->set_size(10);

	$grey_code_f =& $workbook->add_format();
	$grey_code_f->set_bold(0);            // Make it bold
	$grey_code_f->set_size(12);
	$grey_code_f->set_fg_color(22);
	$grey_code_f->set_align('center');

	//$formatbc->set_size(14);
	$myxls->write(1, 1, "ATTENDANCE SUMMARY",$header1);
	$myxls->write(2, 1, $reportname,$header2);
	if($start){
		$duration='Period : ( '.date(" M jS, Y", $start).' - '.date(" M jS, Y", $end).')';
		$myxls->write(4, 1, $duration, $formatbc);
	}
	//$myxls->write(4, 0, $duration);

	$myxls->set_column(3,100,15);//Added By Hina
	$i = 6;
	$j = 0;
	$a=0;

	$a=0;

	foreach ($data->head as $heading){
		$heading = str_replace('<br/>','',$heading);
		$heading = trim($heading);
		$myxls->write_string($i, $j, $heading,$header2);

		$col_size = strlen($heading);
		$col_size+=6;

		if(preg_match('/^NAME/i',$heading)){
			$col_size=25;
		}
		$myxls->set_column($j,$j,$col_size);
		//added By Hina Yousuf
		if($data->headspan[$a]==4){
			$myxls->merge_cells($i,$j,$i,$j+3);
			$j+=4;
		}
		if($data->headspan[$a]==3){
			$myxls->merge_cells($i,$j,$i,$j+2);
			$j+=3;
		}
		if($data->headspan[$a]==1){
			$j++;
		}
		//$j++;
		$a++;//end
		//$j++;
	}
	//$myxls->merge_cells(1,0,1,$j-1);
	//$myxls->merge_cells(2,0,2,$j-1);
	//$myxls->merge_cells(4,1,4,3);

	$i = 7;
	$j = 0;

	///Added By Hina Yousuf


	foreach ($data->data as $row) {
		foreach ($row as $cell) {
			foreach ($cell as $cel) {
				$myxls->write_string($i, $j, strip_tags($cel->text),$normal1);
				$j++;

			}
		}


	}
	///end
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
				///

				if(preg_match('/^!!r/',$cell)){
					$cell = str_replace("!!r",'',$cell);
					$myxls->write_string($i, $j++, $cell,$red);
				}
				elseif(preg_match('/^!!g/',$cell)){
					$cell = str_replace("!!g",'',$cell);
					$myxls->write_string($i, $j++, $cell,$green);
				}

				elseif(preg_match('/^!!o/',$cell)){
					$cell = str_replace("!!o",'',$cell);
					$myxls->write_string($i, $j++, $cell,$orange);
				}

				//


				/*if(preg_match('/^!!/',$cell)){
					$cell = str_replace("!!",'',$cell);
					$myxls->write_string($i, $j++, $cell,$grey_code_f);
					}*/
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


function ExportToPDF($content){
	//echo "pdf";
	global $CFG;

	//$pdf=new PDF();
	//Column titles
	//$header=$data->tabhead;
	// create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

	// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('');
	$pdf->SetTitle('Feedback Report');
	$pdf->SetSubject('Feedback Report');

	// remove default header/footer
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);

	// set default monospaced font
	//$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

	//set margins
	//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);


	//set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

	//set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

	//set some language-dependent strings
	$pdf->setLanguageArray($l);

	// ---------------------------------------------------------

	// set font
	$pdf->SetFont('helvetica', '', 8);

	// add a page
	$pdf->AddPage('P','A4');
	ob_clean();
	// print a line using Cell()
	//$pdf->Cell(0, 10, 'Example 002', 1, 1, 'C');
	$htmcont = ImprovedTable($content);
	$pdf->writeHTML($htmcont, true, false,false,false,'');
	//echo $htmcont;
	// ---------------------------------------------------------

	//Close and output PDF document
	$pdf->Output("Feedback_Report", 'D');
	exit;

}
//

function ImprovedTable($name)
{

	return $name;

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
