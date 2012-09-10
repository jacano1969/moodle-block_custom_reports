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

	$block_custom_reports_capabilities = array(
		'block/custom_reports:getattendancereport'	=> array(
			'captype'	=> 'read',
			'contextlevel' => CONTEXT_SYSTEM,
			'legacy'	=> array(
				'guest' 			=>	CAP_PREVENT,
				'student'			=>	CAP_PREVENT,
				'teacher'			=>	CAP_PREVENT,
				'editingteacher'	=> 	CAP_PREVENT,
				'coursecreator'		=> 	CAP_ALLOW,
				'admin'				=> 	CAP_ALLOW
			)
		),
		'moodle/course:downloadmaterial' => array(

        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),
		'block/custom_reports:getauditreport'	=> array(
			'captype'	=> 'read',
			'contextlevel' => CONTEXT_SYSTEM,
			'legacy'	=> array(
				'guest' 			=>	CAP_PREVENT,
				'student'			=>	CAP_PREVENT,
				'teacher'			=>	CAP_PREVENT,
				'editingteacher'	=> 	CAP_PREVENT,
				'coursecreator'		=> 	CAP_ALLOW,
				'admin'				=> 	CAP_ALLOW
			)
		),
                'block/custom_reports:getfeedbackreport'        => array(
                        'captype'       => 'read',
                        'contextlevel' => CONTEXT_SYSTEM,
                        'legacy'        => array(
                                'guest'                         =>      CAP_PREVENT,
                                'student'                       =>      CAP_PREVENT,
                                'teacher'                       =>      CAP_PREVENT,
                                'editingteacher'        =>      CAP_PREVENT,
                                'coursecreator'         =>      CAP_ALLOW,
                                'admin'                         =>      CAP_ALLOW
                        )
                )
,

                'block/custom_reports:getstudentsList'  => array(
                        'captype'       => 'read',
                        'contextlevel' => CONTEXT_SYSTEM,
                        'legacy'        => array(
                                'guest'                         =>      CAP_PREVENT,
                                'student'                       =>      CAP_PREVENT,
                                'teacher'                       =>      CAP_PREVENT,
                                'editingteacher'        =>      CAP_PREVENT,
                                'coursecreator'         =>      CAP_PREVENT,
                                'admin'                         =>      CAP_ALLOW,
                                'examdept'                      =>  CAP_ALLOW,
                                'acb'                           =>  CAP_ALLOW
                        )
                ),

                'block/custom_reports:getusagereport'   => array(
                        'captype'       => 'read',
                        'contextlevel' => CONTEXT_SYSTEM,
                        'legacy'        => array(
                                'guest'                         =>      CAP_PREVENT,
                                'student'                       =>      CAP_PREVENT,
                                'teacher'                       =>      CAP_PREVENT,
                                'editingteacher'        =>      CAP_PREVENT,
                                'coursecreator'         =>      CAP_ALLOW,
                                'admin'                         =>      CAP_ALLOW
                        )
                )

		,
                'block/custom_reports:getmcr'   => array(
                                        'captype'       => 'read',
                                        'contextlevel' => CONTEXT_SYSTEM,
                                        'legacy'        => array(
                                                'guest'                         =>      CAP_PREVENT,
                                                'student'                       =>      CAP_PREVENT,
                                                'teacher'                       =>      CAP_PREVENT,
                                                'editingteacher'        =>      CAP_PREVENT,
                                                'coursecreator'         =>      CAP_PREVENT,
                                                'admin'                         =>      CAP_ALLOW,
                                                'acb'                           =>  CAP_ALLOW
)
),
		
                'block/custom_reports:getembaregreport' => array(
                                        'captype'       => 'read',
                                        'contextlevel' => CONTEXT_SYSTEM,
                                        'legacy'        => array(
                                                'guest'                         =>      CAP_PREVENT,
                                                'student'                       =>      CAP_PREVENT,
                                                'teacher'                       =>      CAP_PREVENT,
                                                'editingteacher'        =>      CAP_PREVENT,
                                                'coursecreator'         =>      CAP_PREVENT,
                                                'admin'                         =>      CAP_ALLOW

)
)
,
		'block/custom_reports:get_student_semester_report'	=> array(
					'captype'	=> 'read',
					'contextlevel' => CONTEXT_SYSTEM,
					'legacy'	=> array(
						'guest' 			=>	CAP_PREVENT,
						'student'			=>	CAP_PREVENT,
						'teacher'			=>	CAP_PREVENT,
						'editingteacher'	=> 	CAP_PREVENT,
						'coursecreator'		=> 	CAP_PREVENT,
						'admin'				=> 	CAP_ALLOW
						
)
)




	);
?>
