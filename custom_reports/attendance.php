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
if($type==1){
	$reportname="Attendance Report";
}
if($type==2){
	$reportname="Short Attendance Report";
}
if($type==3){
	$reportname="Absentee Report (Period Wise)";
}
$export = optional_param('export',false, PARAM_BOOL);
$template = optional_param('template',false, PARAM_BOOL);
$content = $_POST['content'];//optional_param('content',"", PARAM_ALPHANUM);
if($template){
	//echo $content;
	ExportToPDF($content);

}

$perd = optional_param('perd',false, PARAM_BOOL);
$start=$_POST['start'];
$end=  $_POST['end'];

$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);

if($categoryid!=-1)
require_capability('block/custom_reports:getattendancereport', $context);
$report=get_string('attendance_custom_reports', 'block_custom_reports');
$navlinks[] = array('name' => get_string('attendance_custom_reports', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
print_header('Attendance Custom Report', 'Attendance Custom Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

if($courses = get_courses($categoryid, '', 'c.id, c.fullname, c.startdate, c.idnumber, c.shortname') or $export or $perd){
	$mform = new mod_custom_reports_view_attendance_report_form('attendance.php', array('courses'=>$courses, 'categoryid'=>$categoryid,'report'=>$report));
	if($fromform = $mform->get_data() or $export or $perd){
		$cselected = array();
		if($export or $perd){

			$export_courses = required_param('courses');

			if($type==1){
				$sessions_margin = array_reverse(explode(",", required_param('sessions')));
			}

			//echo "select id, fullname, startdate, idnumber, shortname from {$CFG->prefix}courses where id IN ({$export_courses})";
			$courses = $DB->get_records_sql("select id, fullname, startdate, idnumber, shortname from {course} where id IN ({$export_courses})");
			$export_courses_sessions = "";
		}else{

			$export_courses = "";
		}
		$temp="";

		if($type==2 || $type==3){

			$table = new html_table();
			$table->head = array();

			/*$table->head[] = "S/No";
			 $table->align[] = 'center';
			 $table->size[] = '40px';
			 $table->headspan[] = 1;*/
		}


		$i=0;
		foreach($courses as $course){

			if((!$export AND $fromform->{'c'.$course->id}=='true') OR ($export or $perd)){
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
				$query = "SELECT u.* from mdl_user u
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

				if($type==2  ||  $type==3){
					$table->head[] = $course->fullname;
					$table->align[] = 'center';
					$table->size[] = '40px';
					$table->headspan[] = 3;
					$row=0;
					$table->data[$row][]="Fullname";
					$table->data[$row][]="Reg: No";
					$table->data[$row][]="Abs %";
					/*if(!$export){
						$table->data[$row][]="Template";
						}*/

				}
				$cselected["shortname"][] = $course->shortname;
				$cselected["idnumber"][] =  $course->idnumber;
				$cselected["students"][] =  $DB->get_records_sql($query);
				if($type==2 || $type==3){

					$students =  $DB->get_records_sql($query);
					$row=1;
					$flag=false;
					foreach($students as $student){


						if($perd or ($export && $start)){

							$where="sessdate between $start and $end";
						}

						$attendance = get_percent_absent($student->id, $course,$where);

						if($type==3){
							if($attendance ==100){

								for ($k=0;$k<$i;$k++){
									if( $table->data[$row][$k]==""){
										$table->data[$row][$k]="---";
										$table->data[$row][$k+1]="---";
										$table->data[$row][$k+2]="---";
										/*if(!$export){
											$table->data[$row][$k+3]="---";
											}*/
										//$row++;

									}
								}
								/*if($table->data[$row-1][$i]=="---"){
								 $table->data[$row-1][$i]=$student->firstname.' '.$student->lastname;
								 $table->data[$row-1][$i+1]=$student->idnumber;
								 $table->data[$row-1][$i+2]=$attendance."%";

								 //$row++;
								 }else{*/
								//}else{
								$warning[$student->id][]=$course->fullname."|".$student->firstname." ".$student->lastname."|".$attendance."%"."|".$course->startdate."|".$student->idnumber."|".$student->address."|".$student->phone2."|".$student->fathername;
								$table->data[$row][$i]=  $student->firstname.' '.$student->lastname;
								$table->data[$row][$i+1]=$student->idnumber;
								$table->data[$row][$i+2]=$attendance."%";
								/*if(!$export){
									$table->data[$row][$i+3]='<form method="post" style="display: inline; margin: 0; padding: 0;"><input type="hidden" name="template" value="true" /><input type="hidden" name="sname" value='.$student->firstname.'-'.$student->lastname.' /><input type="submit" value="Download Template" /></form>';
									}*/
								$row++;

								//}


							}
							elseif($attendance < 100 && $table->data[$row][$i-1]!="" ){
								$table->data[$row][$i]  ="---";//$student->idnumber."<br/>".$student->firstname.' '.$student->lastname."<br/>".$attendance."%";
								$table->data[$row][$i+1]="---";
								$table->data[$row][$i+2]="---";
								/*if(!$export){
									$table->data[$row][$i+3]="---";

									}*/
								//$row++;
							}

						}
						if($type==2){
							if($attendance > 25.99){

								for ($k=0;$k<$i;$k++){
									if( $table->data[$row][$k]==""){
										$table->data[$row][$k]="---";
										$table->data[$row][$k+1]="---";
										$table->data[$row][$k+2]="---";
										//$row++;

									}
								}
								$flag=false;
								//for ($k=0;$k<$i;$k++){
								/*for($l=$row;$l>0; $l--)
								{
								if($table->data[$l][$i]=="---"){
								$flag=true;
								$row1=$l;

								}

								}*/
								//echo "lll ".$l;
								//if($table->data[$row-1][$i]=="---"){
								//if($flag==true){
								//$table->data[$l][$i]="9".$row1.$student->firstname.' '.$student->lastname;
								//$table->data[$l][$i+1]=$student->idnumber;
								//$table->data[$l][$i+2]=$attendance."%";

								//$row++;
								//}else{
								$warning[$student->id][]=$course->fullname."|".$student->firstname." ".$student->lastname."|".$attendance."%"."|".$course->startdate."|".$student->idnumber."|".$student->address."|".$student->phone2."|".$student->fathername;

								$table->data[$row][$i]=$student->firstname.' '.$student->lastname;
								$table->data[$row][$i+1]=$student->idnumber;
								$table->data[$row][$i+2]=$attendance."%";
								$row++;
								//}


							}
							elseif($attendance < 25.99 && $table->data[$row][$i-1]!="" ){
								$table->data[$row][$i]="---";//$student->idnumber."<br/>".$student->firstname.' '.$student->lastname."<br/>".$attendance."%";
								$table->data[$row][$i+1]="---";
								$table->data[$row][$i+2]="---";
								//$row++;
							}
						}

						//$row++;
						// }
					}
				}

				////
				if($type==1){
					$cselected["startdate"][] = $course->startdate;
					if($export)
					$cselected["margin"][] = array_pop($sessions_margin);
					else
					$cselected["margin"][] = $fromform->{'session'.$course->id};
				}

			}
			if($fromform->{'c'.$course->id}=='true' and !$export){
				$export_courses .= $course->id.",";
				if($type==1){
					$export_courses_sessions .= $fromform->{'session'.$course->id}.",";
				}
			}

			$i+=3;



		}
		//for warning letter templates
		$content="";
		$classs=$DB->get_record_sql("SELECT path from {course_categories} cat WHERE id =$categoryid");
		$path=explode("/", $classs->path);

		$school=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE path like '/$path[1]' and parent=0");


		$degree= $class->name;
		$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =$categoryid");
			
		$semester= $class->name;
		$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =(SELECT parent from {course_categories} cat WHERE id =$categoryid)");
			
		$degree= $class->name;
		$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =(SELECT parent from {course_categories} cat WHERE id =$class->id)");
		$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =(SELECT parent from {course_categories} cat WHERE id =$class->id)");
		$graduate= $class->name;
		foreach ($warning as $warnin){
			$i=1;
			//$image="NUST_Logo.jpg";
			//$content.='<img src="NUST_Logo.jpg"></img>';

			$content1='';
			$content1.='<br/><br/><table align="center" border="1"><tr><td>S/No</td><td>Subject</td><td>Attendance</td></tr>';
			foreach ($warnin as $warn){
				$letter = explode("|", $warn);
				$content1.='<tr><td>'.$i.'</td><td>'.$letter[0].'</td><td>'.$letter[2].'</td></tr>';
				$i++;
			}
			$content1.='</table>';
			$content.='<table  border="0"><tr><td><img src="NUST_Logo.jpg" height="52" width="52" /> </td><td>'.$school->name.
'<br/>NUST H-12, Islamabad.
<br/>Tel: 051-9085 [School Contact Number]
<br/>[File Number]
<br/>[Month], [Year]</td></tr></table>';
			$content.='<br/><br/><table border="0"><tr><td>To: Mr. '.$letter[7].' (Father of '.$letter[1].') </td></tr>
            <tr><td>Address: '.$letter[5].'</td></tr>
            <tr><td>Telephone: '.$letter[6].'</td></tr></table>';
			$content.='<br/>Subject: <b>Attendance – ['.$graduate.'] ['.$degree.'] – [Year of Enrollment] (['.$semester.'] )</b> <br/>';
			$content.='<li>1.I would like to inform you that your ward ['.$letter[1].'] [ '. $letter[4].']  student of '.$semester.' '  .$degree.' was absent for numerous periods in the subject/s shown below during the current semester which commenced on '.date("d-m-Y",$letter[3]) .':-</li> ';
			$content.=$content1;
			$content.='<br/><li>2.The present attendance of your ward is falling short of the minimum requisite criteria of 75% under the provision of Para 44 (c) (viii), Chapter-VI of NUST Statues/ Regulations. You are, therefore, required to please ensure his/her regularity in all subjects, failing which institute authorities will be forced to take strict action and he/she will not be allowed to take the end semester examination in respective subject/s.</li>';
			$content.='<br/><li>3.Please acknowledge the receipt.</li><br/><br/>';
			$content.='<table align="right"><tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td>Deputy Controller of Examinations([Name of Deputy Controller of Examinations])</td></tr></table><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>';
			$content.='</table>';
		}
		//echo $content;
		///
			
			


		///


		if($type==1){
			$table = new html_table();
			$table->head = array();

			$table->head[] = 'S.No';
			$table->align[] = 'center';
			$table->size[] = '40px';
			$table->headspan[] = 1;

			$table->head[] = 'Registration No';
			$table->align[] = 'center';
			$table->size[] = '220px';
			$table->headspan[] = 1;

			$table->head[] = "Name";
			$table->align[] = 'center';
			$table->size[] = '150px';
			$table->headspan[] = 1;
			$user_sessions=array();
			$TotalSessions=0;

			for($i=0; $i<count($cselected["id"]); $i++){
				$temp_stu = array_slice($cselected["students"][$i],0,1);
				$temp_course = $DB->get_record('course', array('id'=> $cselected['id'][$i]));
				$temp_course->attendance_margin = $cselected['margin'][$i];

				$courseCol = $cselected['name'][$i]."<br/>".$max_sessions;

				$table->headspan[] = 4;
				$table->head[] = $courseCol;
				$table->align[] = 'center';
				$table->size[] = '25px';

				if($i==0){

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
					$row3->cells = array($cell1, $cell2,$cell3);
				}

				$cell9 = new html_table_cell();
				$cell9->text =  "<b>Present %Age</b>";
				$cell9->colspan = 1;

				$cell10 = new html_table_cell();
				$cell10->text =  "<b>Absent %Age</b>";
				$cell10->colspan = 1;
				$cell11 = new html_table_cell();
				$cell11->text =  "<b>Course Sessions</b>";
				$cell11->colspan = 1;
				$cell12 = new html_table_cell();
				$cell12->text =  "<b>Sessions Missed</b>";
				$cell12->colspan = 1;

				$row1->cells[] = $cell5;
				$row3->cells[] = $cell9;
				$row3->cells[] = $cell10;
				$row3->cells[] = $cell11;
				$row3->cells[] = $cell12;


				foreach($cselected["students"][$i] as $student){
					if(!in_array($student->id, $students["userid"])){
						$students["userid"][] = $student->id;
						$students["idnumber"][] = $student->idnumber;
						$students["name"][] = $student->firstname.' '.$student->lastname;
					}
				}
			}
			$cell14 = new html_table_cell();
			$cell14->text =  "";
			$cell14->colspan = 1;
			$row3->cells[] = $cell14;
			$row3->cells[] = $cell14;
			$row3->cells[] = $cell14;
			$table->data = array($row3);

			$table->head[] = 'Total Sessions';
			$table->align[]='center';
			$table->size[]='';
			$table->headspan[] = 1;

			$table->head[] = 'Total Absents';
			$table->align[]='center';
			$table->size[]='';
			$table->headspan[] = 1;


			$table->head[] = 'Cummulative Absentees(%)';
			$table->align[] = 'center';
			$table->size[] = '';
			$table->headspan[] = 1;
			$row_ite=0;
			$row=2;
			for(; $row_ite<count($students["userid"]); $row_ite++){
				$table->data[$row][] = $row_ite+1;
				$table->data[$row][] = $students["idnumber"][$row_ite];
				$table->data[$row][] = $students["name"][$row_ite];
				$all_sessions_missed=0;
				$all_sessions=0;
				for($j=0; $j<count($cselected['id']); $j++){
					$course = $DB->get_record('course', array('id'=> $cselected['id'][$j]));
					$course->attendance_margin = $cselected['margin'][$j];
					if(!key_exists($students["userid"][$row], (array)$cselected["students"][$j])){
						$table->data[$row][] = '---';
						$table->data[$row][] = '---';
						$table->data[$row][]='---';
						$table->data[$row][]='---';
						continue;
					}
					$attendance = get_percent_absent($students["userid"][$row_ite], $course);
					$course_sess_att = get_grade($students["userid"][$row_ite],$course);
					$course_sessions = get_maxgrade($students["userid"][$row_ite],$course);
					$all_sessions+=$course_sessions;
					$course_sess_missed = $course_sessions - $course_sess_att;
					$all_sessions_missed+=$course_sess_missed;
					if($attendance > 25.99){
						if(!$export){

							$table->data[$row][] = (100-$attendance).'%';
							$table->data[$row][] = '<div style="background: red;">'.$attendance.'%</div>';
							$table->data[$row][]=$course_sessions;
							$table->data[$row][]=$course_sess_missed;
						}
						else{
							$table->data[$row][] = (100-$attendance).'%';
							$table->data[$row][] = '!!r'.$attendance.'%';
							$table->data[$row][]=$course_sessions;
							$table->data[$row][]=$course_sess_missed;
						}
					}
					else{
						if($attendance >=20.00 && $attendance <=25.99){
							if(!$export){

								$table->data[$row][] = (100-$attendance).'%';
								$table->data[$row][] = '<div style="background: orange;">'.$attendance.'%</div>';
								$table->data[$row][]=$course_sessions;
								$table->data[$row][]=$course_sess_missed;
							}
							else{
								$table->data[$row][] = (100-$attendance).'%';
								$table->data[$row][] = '!!o'.$attendance.'%';
								$table->data[$row][]=$course_sessions;
								$table->data[$row][]=$course_sess_missed;
							}
						}
						if($attendance <20.00){
							if(!$export){

								$table->data[$row][] = (100-$attendance).'%';
								$table->data[$row][] = '<div style="background: green;">'.$attendance.'%</div>';
								$table->data[$row][]=$course_sessions;
								$table->data[$row][]=$course_sess_missed;
							}
							else{
								$table->data[$row][] = (100-$attendance).'%';
								$table->data[$row][] = '!!g'.$attendance.'%';
								$table->data[$row][]=$course_sessions;
								$table->data[$row][]=$course_sess_missed;
							}
						}
						/*$table->data[$row][] = (100-$attendance).'%';
						 $table->data[$row][] = $attendance.'%';
						 $table->data[$row][]=$course_sessions;
						 $table->data[$row][]=$course_sess_missed;*/
					}
				}
				$table->data[$row][]=$all_sessions;
				$table->data[$row][]=$all_sessions_missed;
				$cum_abs = round(($all_sessions_missed/$all_sessions)*100,2);
				if($cum_abs > 25.99){
					if(!$export){
						$table->data[$row][] = '<div style="background: #red;">'.$cum_abs.'%</div>';
					}
					else{
						$table->data[$row][] = '!!'.$cum_abs.'%';
					}
				}
				else{
					$table->data[$row][] = $cum_abs.'%';
				}
				$row++;
			}

		}

		if($export){

			$table->category = $category->name;
			//$table->duration = date("d M Y", $cselected["startdate"][0]).' to '.date("d M Y", time('now'));
			ExportToExcel($table);
		}
		else{

			echo '<div style="text-align: center; font-weight: bold;">ATTENDANCE SUMMARY <br>ABSENTEES RECORDS (Percentage)</div>';
			echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">';
			if($type==3){
				if($perd){
					echo '<div style="text-align: center; font-weight: bold;">Period : ( '.date(" M jS, Y", $start).' - '.date(" M jS, Y", $end).' )</div>';
				}



				foreach ($courses as $course){
					$startdate= $course->startdate;
				}

				if(!$perd){
					$time->end =$startdate;
					$enddate =strtotime("+18 weeks", $time->end);
					echo '<div style="text-align: center; font-weight: bold;">Period : ( '.date(" M jS, Y", $startdate).' - '.date(" M jS, Y", $enddate).' )</div>';
				}

				echo '<form method="post" style="display: inline; margin: 0; padding: 0;">';
				echo 			'<input type="hidden" name="courses" value="'.rtrim($export_courses, ',').'" />';
				echo 			'<input type="hidden" name="id" value="'.$categoryid.'" />';
				echo 			'<input type="hidden" name="perod" value="'.$perd.'" />';
				echo 			'<input type="hidden" name="type" value="'.$type.'" />';

				echo "<b>Start Period</b>";
				echo "<select name='start' id='start'>";
				$date=array();
				$time->end =$startdate;
				$enddate =strtotime("+18 weeks", $time->end);
				//if(!$perd){
				echo '<div style="text-align: center; font-weight: bold;">Period : ( '.date(" M jS, Y", $startdate).' - '.date(" M jS, Y", $enddate).' )</div>';
				//}
				do{
					echo '<option value="'.$time->end.'">'.date(" M jS, Y", $time->end).'</option>';
					$time->end = strtotime("+1 days", $time->end);
					//$date[date(" M jS, Y", $time->end)] =date(" M jS, Y", $time->end

				}while($time->end <= $enddate);
				echo "</select>";

				echo "&nbsp;&nbsp;<b>End Period</b>";
				echo "<select name='end' id='end'>";
				$date=array();
				$time->end =$startdate;
				do{
					echo '<option value="'.$time->end.'">'.date(" M jS, Y", $time->end).'</option>';
					$time->end = strtotime("+1 days", $time->end);
					//$date[date(" M jS, Y", $time->end)] =date(" M jS, Y", $time->end);

				}while($time->end <= $enddate);


				echo "</select>";
				echo 			'<input type="hidden" name="perd" value="true" /><input type="submit" name="periods" value="Select Period" />';
				echo "<br/>";
				echo			'</form>';
			}
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
			if($type==3 || $type==2){
				echo			'<form method="post" style="display: inline; margin: 0; padding: 0;">';
				echo 			 "<input type='hidden' name='content' value='".$content."'/>";
				echo 			'<input type="hidden" name="template" value="true" /><input type="submit" value="Download Waring Letters" /></form>';
			}


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
