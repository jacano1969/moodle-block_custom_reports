<?php
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
		'block/custom_reports:getfeedbackreport'	=> array(
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
		
		'block/custom_reports:getusagereport'	=> array(
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
		
		'block/custom_reports:getresults'	=> array(
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
		)
	);
?>