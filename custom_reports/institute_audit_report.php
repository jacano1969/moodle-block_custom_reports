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
//$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
//if($categoryid!=-1)
  //  require_capability('block/custom_reports:getattendancereport', $context);
$navlinks[] = array('name' => get_string('attendance_custom_reports', 'block_custom_reports'), 'link' => null, 'type' => 'activityinstance');
$navigation = build_navigation($navlinks);

if(!$export)
    print_header('Attendance Custom Report', 'Attendance Custom Report', $navigation, '', '', true, '', user_login_string($SITE).$langmenu);

$selected_school= $_POST['school'] ;
$id_name = explode("|", $selected_school);
$id =   $id_name[0]; // school id
$name = $id_name[1]; // school name
$period= $_POST['period'] ;
$printperiod= $period ;
if($period=="all")
{
    $printperiod="";
}
echo '<div style="text-align: center; font-weight: bold;">'.strtoupper($printperiod) .' AUDIT REPORT  '.'</div>';
echo '<div style="text-align: center; font-weight: bold;">  '.$name.'</div>';


echo '<div style="text-align: left; padding-left: 20px; margin: 5px 0;">
<form method="post" style="display: inline; margin: 0; padding: 0;">';
echo '<input type="hidden" name="export" value="true" /><input type="submit" value="Download Excel" />
</form>
<span style="text-align: left; padding-left: 20px; text-decoration: underline;">Duration :- '.date("d M Y", 1272672000).' to '.date("d M Y", time('now')).'</span></div>';

Print_form();

if(isset($_POST['view']) or $export){
    
        $type = $_POST['stats'];
        $period= $_POST['period'] ;
        $selected_school= $_POST['school'] ;
        
        if(isset($_POST['view'])){
            $_SESSION['type']=$type;
            $_SESSION['period']=$period;
            $_SESSION['school']=$selected_school;
        }
        if($export){
            $type = $_SESSION['type'];
            $period= $_SESSION['period'];
            $selected_school= $_SESSION['school'];
        }
        if($period=="weekly"){
            $gap=7;
        }
        if($period=="monthly"){
            $gap=30;
        }
        if($period=="yearly"){
            $gap=365;
        }
        
        $id_name = explode("|", $selected_school);
        $id = $id_name[0];         // school id
        $name = $id_name[1];     // school name
	if($id=="all")
		{
			$context = get_context_instance(CONTEXT_USER, $USER->id);
			require_capability('block/custom_reports:getusagereport', $context);
		}
		else{
			require_capability('block/custom_reports:getattendancereport', $id);
		}
        //$time->timestart=1272672000;
        $time->end =1188604800;
        $school_ids=$DB->get_records_sql("SELECT id FROM {course} WHERE category IN ( SELECT id FROM {course_categories} WHERE path LIKE '/$id%')");
        
        foreach($school_ids as $school_id){
            $ids.= $school_id->id.", ";
        }
        $ids = rtrim($ids, ", ");
        if($type=="Activities"){
            $table = new html_table();
            $table->head = array();
            $table->head[] = 'Period';
            $table->align[] = 'center';
            $table->size[] = '';
            $modules = array('assignment', 'forum', 'quiz', 'resource', 'attforblock', 'turnitintool');
            
            foreach($modules as $module){
            $table->head[] = strtoupper($module);
            $table->align[] = 'center';
            $table->size[] = '';
            }
            $i=0;
            do{
            if($period=="all"){
                //$time->timestart = time();
                
                $time->start =$time->end ;
                $time->end = time();
            }
            else{
                $time->start =$time->end ;
                $time->end = strtotime("+$gap days", $time->start);
            }
            $date =$time->timestart;
            $starttime= date(" M jS, Y", $time->start);
            $endtime= date(" M jS, Y", $time->end);
            $realtime= date(" M jS, Y", $time->timestart);
            $table->data[$i][] =$starttime." - ". $endtime;
            foreach ($modules as $module){             
            if($resource = $DB->get_record_sql("SELECT count(a.id) as total, b.name FROM {course_modules} a, {modules} b 
            WHERE  a.added between  $time->start and $time->end AND  a.course IN ($ids) and a.module = b.id and b.name = ? group by b.name order by b.id",array($module)))
            if($resource->name == 'attforblock')
            if($sessions = $DB->get_record_sql("select count(id) as total from {attendance_sessions}
            where timemodified between  $time->start and $time->end and courseid IN ($ids) 
            and lasttaken != 'NULL'"))
            $table->data[$i][] = $sessions->total;
            else
            $table->data[$i][] = "0";
            else
            $table->data[$i][] = $resource->total;
            else
            $table->data[$i][] = "0";
            }
            $i++;
            //echo $time->timestart."time". time()."<br>";
            if($period=="all"){
            $time->end =  strtotime("+1 days", $time->end );
            }
            
            }while ($time->end <= time());
        }

        else{
            $modules = array('FULL NAME', 'Account Type', 'Time Created', 'Last Access');
        
            if($type=="users"){
                $usertype="1,2,3,4,5,6,7,9,10,15";
            }
            
            if($type=="students"){
                $usertype="5";
            }
            
            if($type=="faculty"){
                $usertype="3,4,10";
            }
        
            $table = new html_table();
            $table->head = array();
        
            $table->head[] = 'Full Name';
            $table->align[] = 'center';
            $table->size[] = '';
            
            $table->head[] = 'Account Type';
            $table->align[] = 'center';
            $table->size[] = '';
            
            $table->head[] = 'Time Created';
            $table->align[] = 'center';
            $table->size[] = '';
            
            $table->head[] = 'Last Access';
            $table->align[] = 'center';
            $table->size[] = '';
            $i=0;
            
            do{
                if($period=="all"){
                    
                    $time->start =$time->end ;
                    $time->end = time();
                }
                else{
                    $time->start =$time->end ;
                    $time->end = strtotime("+$gap days", $time->start);
                }

                $starttime= date(" M jS, Y", $time->start);
                $endtime= date(" M jS, Y", $time->end);
                
                
                
                $i++;
                $table->data[$i][]="";
                $table->data[$i][] ="<b>Period ".$starttime." - ". $endtime."</b>" ;
                $sql="SELECT  firstname, lastname , name , u.timemodified as time , lastaccess  
                                                    FROM mdl_user u JOIN mdl_role_assignments ra ON ra.userid = u.id
                                                    JOIN mdl_role r ON ra.roleid = r.id
                                                    JOIN mdl_context c ON ra.contextid = c.id
                                                    WHERE r.id IN ($usertype) 
                                                    AND u.timemodified between  $time->start and $time->end
                                                    AND c.contextlevel =50
                                                    AND c.instanceid IN ($ids)
                                                    GROUP BY username";
               
                
                $users = $DB->get_records_sql($sql);
                
                $i++;
                $count=0;
                foreach ($users as $user){ 
                    
                    $table->data[$i][] = $user->firstname." ".$user->lastname;
                    $table->data[$i][] = $user->name;
                    $table->data[$i][] = date(" M jS, Y", $user->time);
                    $table->data[$i][] = date(" M jS, Y", $user->lastaccess); 
                    $i++;
                    $count++;
                }
                
                $table->data[$i][] ="" ;
                $table->data[$i][] = "<b>Total no of users:</b>";
                $table->data[$i][] = "<b>".$count."</b>";
                $table->data[$i][] = "";
                $table->data[$i][] = ""; 
                $i++;
                $table->data[$i][]="";
                $table->data[$i][]="";
                $table->data[$i][]="";
                $table->data[$i][]="";
                
                //$i=$i+2;
                
                //echo $time->timestart."time". time()."<br>";
                if($period=="all"){
                    $time->end =  strtotime("+1 days", $time->end );
                }
               
                
                 
            }while ($time->end <= time());        
    }
}

if($export ){
                
                $table->duration = date("d M Y", 1188604800).' to '.date("d M Y", time('now'));
                ExportToExcel($table);
            }

if(isset($_POST['view'])){
    echo html_writer::table($table,$name,$type);
}
echo $OUTPUT->footer();

//================Export to Excel================//

function ExportToExcel($data,$name,$type) {
   global $CFG;
   global $modules;
   global $name,$type;
    //require_once("$CFG->libdir/excellib.class.php");/*
   require_once($CFG->dirroot.'/lib/excellib.class.php');
   $filename = "Institute_audit_report.xls";
   
   $workbook = new MoodleExcelWorkbook("-");
/// Sending HTTP headers
    ob_clean();
    $workbook->send($filename);
/// Creating the first worksheet
    $myxls =& $workbook->add_worksheet('Autid Report');
/// format types
    $formatbc =& $workbook->add_format();
$formatbc1 =& $workbook->add_format();
    $formatbc->set_bold(1);
$myxls->set_column(0, 0, 30);
$myxls->set_column(1, 7, 20);
$formatbc->set_align('center') ;
$formatbc1->set_align('center') ;
    $xlsFormats = new stdClass();
    $xlsFormats->default = $workbook->add_format(array(
                            'width'=>40));
    //$formatbc->set_size(14);
    $myxls->write(0, 2, "AUDIT REPORT", $formatbc);
    $myxls->write(1, 2, $name, $formatbc);
    
    if($type=="Activities"){
        $myxls->write_string(4, 0, "Period", $formatbc);
        $j = 1;
    }
    foreach ($modules as $module)
        $myxls->write_string(4, $j++, strtoupper($module), $formatbc);

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

/////////////////////////////////////////////////////////////////////////////////////////////////////////////   
function Print_form() {
    global $CFG,$DB;
    $sql="SELECT id,name FROM {course_categories} WHERE parent =0";
    $schools =  $DB->get_records_sql($sql);
    echo "<br/><br/><table   border='1'><tr><td>";
    echo "<label><b>Select School:</b></label></td><td>";
    echo "<form name='school_report' method='post' action='institute_audit_report.php'>";
    echo "<select name='school'>";
    foreach($schools as $school){
        $value= $school->id."|".$school->name;
        ?>
        <option value="<?php echo $value; ?>"><?php echo $school->name ?> </option>
        <?php
    }
    echo "</select></td>";
    ?>
    <td> &nbsp;</td><td> &nbsp;</td>
    <td>
    <label><b>Audit Type:</b></label></td>
    <td>
    <input type="radio" name="stats" value="Activities" checked> Activities<br>
    <input type="radio" name="stats" value="users" > Users<br>
    <input type="radio" name="stats" value="students" > Students<br>
    <input type="radio" name="stats" value="faculty" > Teacher (Editing / Non-Editing)</td>
    <td> &nbsp;</td><td> &nbsp;</td>
    <td><label><b>Select Period:</b></label></td>
    <td>
    <select name="period">
        <option value="all">All</option>
        <option value="monthly">Monthly</option>
        <option value="weekly">Weekly</option>
        <option value="yearly">Yearly</option>
    </select></td>
    <td><input type="submit" name="view" value="View"></td></tr></table>
    <?php

}
