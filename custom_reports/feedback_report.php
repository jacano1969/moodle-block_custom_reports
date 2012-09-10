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
	
	<script type="text/javascript" src="jquery-1.3.2.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			
			$('#loader').hide();
			$('#show_heading').hide();
			
			$('#school_').change(function(){
			
				$('#show_departments').fadeOut();
				$('#loader').show();
				// alert($('#school_').val());
				$.post("get_chid_categories.php", {
				
					parent_id: $('#school_').val(),
				}, function(response){
					
					setTimeout("finishAjax('show_departments', '"+escape(response)+"')", 400);
				});
				return false;
			});
		});

		function finishAjax(id, response){
		  $('#loader').hide();
		  if($('#school_').val()!="0|NUST CAMPUS"){
		  	$('#show_heading').show();
		  }
		  if($('#school_').val()=="0|NUST CAMPUS"){
			  	$('#show_heading').hide();
	      }
		  $('#'+id).html(unescape(response));
		  $('#'+id).fadeIn();
		} 

		function alert_id()
		{
			if($('#sub_category_id').val() == '')
			alert('Please select a sub category.');
			else
			alert($('#sub_category_id').val());
			return false;
		}
	</script>
<?php
    require_login($course->id);
    session_start();
    $dept          = optional_param('dept', "", PARAM_ALPHANUM);//get user sub group.
    $school_= $_GET['school_'];
	$unique_courses[]=-1;
	$hod=0;
	$observer=0;
	$user->id=0;
    $export = optional_param('export',false, PARAM_BOOL);
    $context = get_context_instance(CONTEXT_COURSECAT, $categoryid); 
    $navlinks[] = array('name' => get_string('feedback_report', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
    $navigation = build_navigation($navlinks);        
    
    if(!$export)
        print_header('Attendance Custom Report', 'Attendance Custom Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);
    if(isset($_POST['report']) or $export)
    {
 		$currentSemester=strtotime("-6 months", time());
        $department=$_POST['dept'];    
        $school=$_POST['school_'];                 
        if(isset($_POST['report'])){                           
            $_SESSION['department'] =$department;
            $_SESSION['school'] =$school;
        }
        if($export){                         
            $department =$_SESSION['department'];
            $school =$_SESSION['school'];
        }
        $id_name = explode("|", $school);        
        $id = $id_name[0];         // school id
        $name = $id_name[1];	
		//checking for permissions
		$context = get_context_instance(CONTEXT_COURSECAT, $id);
		
		if($id!=0){					//If the school is selected not NUST
			//check if user is the HOD
			$sql="SELECT u.id
				FROM mdl_user u
				JOIN mdl_role_assignments ra ON ra.userid = u.id
				JOIN mdl_role r ON ra.roleid = r.id
				JOIN mdl_context c ON ra.contextid = c.id
				WHERE r.id=17 and u.id= $USER->id and u.user_subgroup='$department'";
			
			$user=	$DB->get_record_sql($sql);
			if ($user->id == $USER->id && has_capability('block/custom_reports:getfeedbackreport', $context)) {
				$hod=1;
				//echo "hod";
			}
		}
		//check if user is the observer
		$sql="SELECT u.id
				FROM mdl_user u
				JOIN mdl_role_assignments ra ON ra.userid = u.id
				JOIN mdl_role r ON ra.roleid = r.id
				JOIN mdl_context c ON ra.contextid = c.id
				WHERE r.id = 9 and u.id= $USER->id ";
		
		$user=	$DB->get_record_sql($sql);
		if ($user->id == $USER->id && has_capability('block/custom_reports:getfeedbackreport', $context)) {
			$observer=1;
			//echo "observer";
		}
	    
		
		    $context = get_context_instance(CONTEXT_USER, $USER->id);
		    if( has_capability('block/custom_reports:getfeedbackreport', $context)){
		   	 $admin=1;
		   	// echo  "admin";
		}
		
		
		if ($hod== 1 || $observer==1 || $admin==1) {
			echo "<br/>";     
			echo '<div style="text-align: center; font-weight: bold;">FACULTY FEEDBACK REPORT <br></div>';
			if($department!=""){
				echo '<div style="text-align: center; font-weight: bold;">SCHOOL:&nbsp;'.$name .'<br></div>';	
				echo '<div style="text-align: center; font-weight: bold;">Department:&nbsp;'.$department .'<br></div>';
			}
			else
			{
				echo '<div style="text-align: center; font-weight: bold;">NUST CAMPUS <br></div>';
			
			}
		//	echo '<div style="text-align: center; font-weight: bold;">Department:&nbsp;'.$department .'<br></div>';
			echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">
						<form method="post" style="display: inline; margin: 0; padding: 0;">';
			echo "<b>Download:</b>";
			echo "<select name='downloadType'>";
      		        echo '<option value="pdf">Download in pdf Format </option>';
		        echo '<option value="Excel">Download in Excel Format </option>';
		        echo '</select>';


			echo             '<input type="hidden" name="export" value="true" /><input type="submit" value="Download" />
						</form>';                
			echo "<br/>";
			$headings=array('Teacher','Subject','Class','Average','Rating');
			$feedback_no=$_POST['feedback_no'];                
			$fb = explode("|", $feedback_no);
                        $feedback_no=$fb[0];
                        $feedbak_no=$fb[1];

			if(isset($_POST['report'])){               
				$_SESSION['feedback_no']=$feedback_no;	
				  $_SESSION['feedbak_no']=$feedbak_no;

			}
			
			if($export){              
				$feedback_no=$_SESSION['feedback_no'];		
				 $feedbak_no=$_SESSION['feedbak_no'];
			}
			$no_of_departments=0;
			$table = new html_table();
			$table->head = array();
			
			$table->head[] =  'Teacher';
			$table->align[] = 'center';
			$table->size[] = '';			

			$table->head[] =  'Subject';
			$table->align[] = 'center';
			$table->size[] = '';
				
			$table->head[] =  'Class';
			$table->align[] = 'center';
			$table->size[] = '';
				
			$table->head[] =  'Average';
			$table->align[] = 'center';
			$table->size[] = '';
				
			$table->head[] =  'Rating';
			$table->align[] = 'center';
			$table->size[] = '';
			
			////get course of the selected department
			if($id!=0){
				$user_subgroup="AND user_subgroup = '$department'";
			}
			if($id==0){
				$user_subgroup="";
			}
			$sql="SELECT u.id, firstname,lastname
				FROM mdl_user u
				JOIN mdl_role_assignments ra ON ra.userid = u.id
				JOIN mdl_role r ON ra.roleid = r.id
				JOIN mdl_context c ON ra.contextid = c.id
				WHERE r.id =3
				$user_subgroup
				GROUP BY u.id
				ORDER BY firstname ASC,lastname ASC";
				//echo $sql;
				$users =  $DB->get_records_sql($sql);
				$i=0;
				foreach($users as $user){           
					$sql="SELECT e.courseid as courseid, fullname,e.timecreated as timecreated
						FROM mdl_user_enrolments ue
						JOIN mdl_enrol e ON ( e.id = ue.enrolid )
						JOIN mdl_course c ON ( c.id = e.courseid )
						AND ue.userid =$user->id AND c.startdate>$currentSemester";
						//echo $sql."<br/>";
						$courses =  $DB->get_records_sql($sql);
						foreach($courses as $course){
							$context = get_context_instance(CONTEXT_COURSE, $course->courseid, MUST_EXIST);
	//Added later
/*                                                $sql = "SELECT firstname, lastname
                                                                FROM mdl_user u
                                                                JOIN mdl_user_enrolments ue ON ( ue.userid = u.id )
                                                                WHERE u.id
                                                                IN (
                                                                
                                                                SELECT ra.userid
                                                                FROM mdl_role_assignments ra
                                                                WHERE ra.roleid =3
                                                                AND contextid =$context->id)";
                                                //echo $course->fullname."-".$sql;
                                                $teachers=$DB->get_records_sql($sql);
                                                $faculty="";
                                                foreach($teachers as $teacherz){
                                                        if($faculty==""){
                                                                $faculty.=$teacherz->firstname." ".$teacherz->lastname;
                                                        }
                                                        else{
                                                                $faculty.=" , ".$teacherz->firstname." ".$teacherz->lastname;
                                                        }

                                                }
*/
                                                //end

							$sql=" SELECT ra.userid FROM mdl_role_assignments ra WHERE ra.roleid =3 AND ra.userid=$user->id and contextid =$context->id ";
								
							if($teacher=$DB->get_records_sql($sql)){
								//echo "<br>befo".$course->courseid;
								
								//echo "<br>".$course->courseid."<br>";
								//$context = get_context_instance(CONTEXT_COURSE, $course->courseid, MUST_EXIST);
								//$sql="SELECT * FROM mdl_user u WHERE u.id IN ( SELECT ra.userid FROM mdl_role_assignments ra WHERE ra.roleid =3 AND ra.userid=$user->id and contextid =$context->id )";
								//echo $sql;
								if(!in_array($course->courseid, $unique_courses)){
									$unique_courses[]=$course->courseid;
									$i++;
									$faculty = explode("(", $feedback->name);
$facultyname=rtrim($faculty[1],")");
if($facultyname==""){
$sql = "SELECT firstname, lastname
                                                                FROM mdl_user u
                                                                JOIN mdl_user_enrolments ue ON ( ue.userid = u.id )
                                                                WHERE u.id
                                                                IN (
                                                                
                                                                SELECT ra.userid
                                                                FROM mdl_role_assignments ra
                                                                WHERE ra.roleid =3
                                                                AND contextid =$context->id)";
                                                //echo $course->fullname."-".$sql;
                                                $teachers=$DB->get_records_sql($sql);
                                                $facultyname="";
                                                foreach($teachers as $teacherz){
                                                        if($facultyname==""){
                                                                $facultyname.=$teacherz->firstname." ".$teacherz->lastname;
                                                        }
                                                        else{
                                                                $facultyname.=" , ".$teacherz->firstname." ".$teacherz->lastname;
                                                        }

                                                }
}		
									$coursefullname =$course->fullname; // course name
									
									$courseid =  $course->courseid;// course id
									$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =(SELECT parent from {course_categories} cat WHERE id =(SELECT category from {course} c where id=$courseid))");                    
									$string = $class->name;
									$find = "Semester";
									if(strstr($string, $find) ==true){                        
										$class=$DB->get_record_sql("SELECT name,id from {course_categories} ct WHERE id =(SELECT parent from {course_categories} cat WHERE id =$class->id)");                    
									}
echo" SELECT id,name from {feedback} f WHERE course =$courseid (name like'%$feedback_no%' or name like'%$feedbak_no%')";
									$feedbacks	 =$DB->get_records_sql("SELECT id,name from {feedback} f WHERE course =$courseid and (name like'%$feedback_no%' or name like'%$feedbak_no%')");
																	
//	if($feedback){
foreach($feedbacks as $feedback){
				$i++;						$coursemodule=$DB->get_record_sql("SELECT id from {course_modules} cm WHERE course =$courseid and instance=$feedback->id and module=23");

	
										if(!$items = $DB->get_records('feedback_item', array('feedback'=>$feedback->id, 'hasvalue'=>1), 'position')) {																
										}							
										$_SESSION['totalavg'] =0;
										$_SESSION['questions']=0;
										foreach($items as $item) {                                        
											$itemobj =  feedback_get_item_class($item->typ);
											if($itemobj== new feedback_item_multichoicerated()){
												 $rowOffset1 = $itemobj->get_average( $item, $mygroupid, $coursefilter);    
											}                        				
										}

//$faculty = explode("(", $feedback->name);
$table->data[$i][]=$facultyname;
//$table->data[$i][]=$faculty;//$user->firstname." ".$user->lastname;		
								$table->data[$i][] ="<a href='{$CFG->wwwroot}/mod/feedback/analysis.php?id={$coursemodule->id}&courseid=&do_show=analysis' target='_blank'>".$coursefullname."</a>";
										$table->data[$i][] =$class->name;        
										$totalmarks=$_SESSION['questions']*5;
										$percentavg=($_SESSION['totalavg']/$totalmarks)*100;
										$no_of_departments++;                    
										$percentavg    = number_format(($percentavg), 2);
										$table->data[$i][] =$percentavg ;    
										$department_avg=$percentavg+$department_avg;
										switch ($percentavg) {
											case  ($percentavg >= 90 && $percentavg <= 100 ):
												$rating ='<div style="background: green;color:white;">Excellent</div>';
												break;
											case  ($percentavg >= 75 && $percentavg <= 89.99 ):
												$rating = '<div style="background: #ADFF2F;">Very Good</div>';
												break;
											case  ($percentavg >= 60 && $percentavg <= 74.99 ):
												$rating = '<div style="background: #FF9900;color:white;">Good</div>';
												break;
											case  ($percentavg >= 40 && $percentavg <= 59.99 ):
												$rating = '<div style="background: #FF4500;color:white;">Satisfactory</div>';
												break;
											case  ($percentavg >0    && $percentavg <= 39.99 ):
												$rating = '<div style="background: red;color:white;">Poor</div>';
												break;
											case  ($percentavg == 0 ):
												$rating =  "Feedback not completed";
											break;
										}                    
										$table->data[$i][] =$rating ;
									}
								}//
							}
						}
				}
			
			$department_avg    =($department_avg/$no_of_departments);
			$i++;
			$table->data[$i][] ="<b>Average Feedback of Department:</b>" ;
			$table->data[$i][] ="";
			$table->data[$i][] ="";
			$department_avg=number_format(($department_avg),2);
			
			switch ($department_avg) {
						case  ($department_avg >= 90 && $department_avg <= 100 ):
							$dept_rating ="Excellent";
							break;
						case  ($department_avg >= 75 && $department_avg <= 89.99 ):
							$dept_rating = "Very Good";
							break;
						 case  ($department_avg >= 60 && $department_avg <= 74.99 ):
							$dept_rating = "Good";
							break;
						case  ($department_avg >= 40 && $department_avg <= 59.99 ):
							$dept_rating = "Satisfactory";
							break;
						case  ($department_avg >0    && $department_avg <= 39.99 ):
							$dept_rating = "Poor";
							break;
						case  ($department_avg == 0 ):
							$dept_rating = "Feedback not completed";
						break;
					}  
			//$department_avg=number_format(($department_avg),2); 
			$table->data[$i][] ="<b>".$department_avg."</b>";				
			$table->data[$i][] ="<b>".$dept_rating."</b>" ;        
        
		}
		else{
			echo "Sorry! You do not have permission to access this report.";
		}
	}
    else{
        echo '<form name="myform" action="feedback_report.php" method="POST">';
        echo "<div align='center'><h1>Faculty Feedback Report</h1></div>";
        echo "<b>Select Feedback Type:</b>";
        echo "<select name='feedback_no'>";
  echo '<option value="First Student Feedback|1st Student Feedback">First Student Feedback</option>';
        echo '<option value="Second Student Feedback|2nd Student Feedback">Second Student Feedback </option>';        
echo '</select>';	
        echo "<br/><b>Select School:</b>";
        $query = "SELECT id,name FROM {course_categories} WHERE parent =0";        
		if($groups = $DB->get_records_sql($query)){
			echo "<select name='school_' id='school_'>";		   
			echo "<option value='0|NUST CAMPUS'>NUST CAMPUS</option>";
			foreach ($groups as $group){
				$school_name = str_replace("&", "and", $group->name);
				$value= $group->id."|".$school_name;
				$selected = ($value == $school_) ? "selected = 'selected'" : "";	
				echo "<option value='{$value}' {$selected} >{$group->name}</option>";
			}
			echo "</select><br/>";
			?>	
			<h4  id="show_heading">Select Department:</h4>
			<div id="show_departments" >
				<img src="loader.gif"  id="loader" alt="" />
			</div>
		<?php 
		}
        echo '<br/><input type="submit" value="View Report" name="report">';
        $OUTPUT->box_start('generalbox categorybox');
        echo '</form>';
    }
	if($export   ){     
    $downloadType=$_POST['downloadType'];  
    if($downloadType=="Excel")       
        ExportToExcel($table);
        if($downloadType=="pdf")
        ExportToPDF($table,$name,$type);
    }
   /* if($export   ){               
        ExportToExcel($table);
    }*/
    if(isset($_POST['report'])  && ($hod== 1 || $observer==1 || $admin==1)){
            echo html_writer::table($table);
    }
	$OUTPUT->box_end();
    echo $OUTPUT->footer();

//================Export to Excel================//    
function ExportToExcel($data,$name,$type) {
   global $CFG;
   global $headings;
   global $name,$type;
   global $department;
    //require_once("$CFG->libdir/excellib.class.php");/*
   require_once($CFG->dirroot.'/lib/excellib.class.php');
   $filename = "Faculty_Feedback_Report.xls";
   
   $workbook = new MoodleExcelWorkbook("-");
/// Sending HTTP headers
    ob_clean();
    $workbook->send($filename);
/// Creating the first worksheet
    $myxls =& $workbook->add_worksheet('Faculty Feedback Report');
/// format types
    $formatbc =& $workbook->add_format();
    $formatbc1 =& $workbook->add_format();
        $formatbc->set_bold(1);
    $myxls->set_column(0, 0, 50);
    $myxls->set_column(1, 7, 20);
    $formatbc->set_align('center') ;
    $formatbc1->set_align('center') ;
    $xlsFormats = new stdClass();
    $xlsFormats->default = $workbook->add_format(array(
                            'width'=>40));
    //$formatbc->set_size(14);
    $myxls->write(0, 2, "FEEDBACK REPORT", $formatbc);
 $myxls->write(1, 2, $name, $formatbc);

    $myxls->write(2, 2,"Department: ". $department, $formatbc);
   
    
    foreach ($headings as $heading)
        $myxls->write_string(4, $j++, strtoupper($heading), $formatbc);

    $i = 5;
    $j = 0;
    foreach ($data->data as $row) {
        foreach ($row as $cell) {
		
            //$myxls->write($i, $j++, $cell);
            if (is_numeric($cell)) {
               $myxls->write_number($i, $j++, strip_tags($cell),$formatbc1);
            } else {
                $myxls->write_string($i, $j++, strip_tags($cell),$formatbc1);
            }
        }
        $i++;
        $j = 0;
    }
    $workbook->close();
    exit;
}

//export to pdf

function ExportToPDF($data,$name,$type){
	//echo "pdf";
	global $CFG;
	global $headings;
	global $name,$type;
	global $department;    
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
	$htmcont = ImprovedTable($headings,$data);
	$pdf->writeHTML($htmcont, true, false,false,false,'');
	//echo $htmcont;
	// ---------------------------------------------------------
	
	//Close and output PDF document
	$pdf->Output("Feedback_Report", 'D');
	exit;

 }
//

function ImprovedTable($headings,$data)
{
	global $CFG;
	global $headings;
	global $name,$type;
	global $department; 
	//echo "improe";
    //Column widths
    //$w=array(40,35,40,45);
    //Header
 $content = $content.'<table cellpadding="2" border="0"><tr><td ><img src="NUST_Logo.jpg" height="52" width="52" /> <font size="15"><b>&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Faculty Feedback Report</b><br/></font></td></tr></table>';
   
    $content = $content.'<h3 align="left">School: '.$name.'</h3>';
    $content = $content.'<h3 align="left">Department: ';   
    $content = $content.$department;
    $content = $content.'</h3>';
    $content = $content.'<table cellpadding="2" border="1"><tr>';
    /*$content = $content.'<td width="3%">-</td><td width="%10">Lecture Hours/Lab</td><td width="13%">-</td>';
    foreach ($lecture as $l){
        $content = $content.'<td width="18">'.$l.'</td>';
    }
    $content = $content.'<td width="18">-</td><td width="18">-</td><td width="25">-</td></tr><tr>';*/
    for($i=0;$i<count($headings);$i++)
        //$this->Cell($w[$i],7,$header[$i],1,0,'C');
        if($i==0){
            $content = $content.'<td width="25%"><b>'.$headings[$i].'</b></td>';
        }elseif($i==1){
            $content = $content.'<td width="40%"><b>'.$headings[$i].'</b></td>';
        }elseif($i==2){
            $content = $content.'<td width="10%"><b>'.$headings[$i].'</b></td>';
        }elseif($i==3){
            $content = $content.'<td width="15%"><b>'.$headings[$i].'</b></td>';
        }elseif($i==4){
		    $content = $content.'<td width="15%"><b>'.$headings[$i].'</b></td>';
		}
    //$this->Ln();
    $content = $content. '</tr>';
    //Data
    foreach($data->data as $row)
    {
        $content = $content. '<tr>';
        $i = 0;
       foreach($row as $col){
           if($i==0){
            $content = $content. '<td width="25%">'. strip_tags($col).'</td>';
           }elseif($i==1){
            $content = $content. '<td width="40%">'. strip_tags($col).'</td>';
           }elseif($i==2){
            $content = $content. '<td width="10%">'. strip_tags($col).'</td>';
           }elseif($i==3){
            $content = $content.'<td width="15%">'. strip_tags($col).'</td>';
           }elseif($i==4){
            $content = $content.'<td width="15%">'.strip_tags($col).'</td>';
           }
           $i = $i + 1;
       }
       $content = $content. '</tr>';
    }
    $content = $content. '</table>';
    //Closure line
    //$this->Cell(array_sum($w),0,'','T');
    //echo 'Hello';
    return  $content;
}
 
?>
