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

require_once($CFG->libdir.'/formslib.php');

class mod_custom_reports_view_attendance_report_form extends moodleform {

	function definition() {

		global $CFG;
		$mform    =& $this->_form;

		$courses    = $this->_customdata['courses'];
		$categoryid = $this->_customdata['categoryid'];
		$report     = $this->_customdata['report'];

		$mform->addElement('header', 'general', get_string('select_courses','block_custom_reports'));
		$mform->addElement('hidden', 'id', $categoryid);
		if($report=="Course Audit Report"){
			$attributes = array("class" => "newclass");
			//Added By Hina Yousuf
			$date=array();
			$time->end =1272672000;
			do{
				$time->end = strtotime("+1 days", $time->end);
				$date[date(" M jS, Y", $time->end)] =date(" M jS, Y", $time->end);



			}while($time->end <= time());
			$options =$date;

			$periodarray=array();

			$periodarray[] =& $mform->createElement('select', 'startperiod', "<b>Start:</b>", $options,$attributes);

			$periodarray[] =& $mform->createElement('select', 'endperiod', "<b>End:</b>", $options,$attributes);
			$mform->addGroup($periodarray, 'period', '<b>Select Period:</b>', array(' to '), false);

			$mform->addElement('html', '<br />');
			//end

			
		}
		if($report=="Missing Lectures Report"){
			$attributes = array("class" => "newclass");
			$weeks=1;
			do{
				if($weeks==1){
					$time=strtotime("+4 days", $courses[0]->startdate);
				}
				else{
					$time=strtotime("+1 week", $time);
						
				}

				$realtime=date(" d-m-Y", $time);
				$noofweeks[$weeks." (Till ".$time.")"] =$weeks." (Till ".$realtime.")";
				$weeks +=1;

					
			}while($weeks <= 18);
			$options =$noofweeks;

			$mform->addElement('select', 'weeks', "<b>No of weeks:</b>", $options,$attributes);



		}

		if($report=="Missing Lectures Report"){
			$attributes = array("class" => "newclass");
			$weeks=0;
			do{



				$skipweeks[$weeks] =$weeks;
				$weeks +=1;

					
			}while($weeks <=5);
				

			$mform->addElement('select', 'skip', "<b>Skip weeks:</b>", $skipweeks,$attributes);



		}

		if($report=="Custom Attendance Report"){

			$attributes = array("class" => "newclass");
			$type=array("1"=>"Attendance Report",
							"2"=>"Short Attendance Report",
							"3"=>"Absentee Report(Period Wise)"
							);

							$options =$type;

							$mform->addElement('select', 'type', "<b>Report Type:</b>", $options,$attributes);

		}
		foreach($courses as $course){
			$mform->addElement('advcheckbox', 'c'.$course->id, '', $course->fullname, array('group'=>1), array('false', 'true'));
			if($report!="Missing Lectures Report"){
				$mform->addElement('text', 'session'.$course->id, 'Sessions', array("size"=>"5"));
			}
		}


	

		$group = array();
		

		$group[] = &$mform->createElement('SUBMIT', 'SUBMITBUTTON', get_string('view_report', 'block_custom_reports'));
		$group[] = &$mform->createElement('RESET', 'RESETBUTTON', get_string('reset'));
		 
		$mform->addGroup($group, 'group2');

		$this->add_checkbox_controller(1, "select all/none");

	}

}
?>
