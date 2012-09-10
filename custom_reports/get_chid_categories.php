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
require('../../config.php');
include('dbcon.php');

if($_REQUEST)
{

	$id 	= $_REQUEST['parent_id'];
	$id_name = explode("|", $id);
    $id = $id_name[0];         // school id
    $name = $id_name[1];

    $query="Select name from {department} where course=$id";
	
	if($sub_groups = $DB->get_records_sql($query)){
		echo "<select name='dept' id='dept'>";
		foreach ($sub_groups as $sub_group){
			$selected = ($sub_group->name == $dept) ? "selected = 'selected'" : "";
			echo "<option value='{$sub_group->name}' {$selected}>{$sub_group->name}</option>";
		}
		echo "</select>";
	}
}
	
?>
