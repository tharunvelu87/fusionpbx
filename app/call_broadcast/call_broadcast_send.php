<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('call_broadcast_send')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//set the max execution time to 1 hour
	ini_set('max_execution_time',3600);

//get the http get values and set as php variables
	$call_broadcast_uuid = $_GET["id"] ?? '';

//get the call broadcast details from the database
	$sql = "select * from v_call_broadcasts ";
	$sql .= "where domain_uuid = :domain_uuid ";
	$sql .= "and call_broadcast_uuid = :call_broadcast_uuid ";
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
	$parameters['call_broadcast_uuid'] = $call_broadcast_uuid;
	$database = new database;
	$row = $database->select($sql, $parameters, 'row');
	if (!empty($row)) {
		$broadcast_name = $row["broadcast_name"];
		$broadcast_start_time = $row["broadcast_start_time"];
		$broadcast_timeout = $row["broadcast_timeout"];
		$broadcast_concurrent_limit = $row["broadcast_concurrent_limit"];
		$broadcast_caller_id_name = $row["broadcast_caller_id_name"] ?? "anonymous";
		$broadcast_caller_id_number = $row["broadcast_caller_id_number"] ?? "0000000000";
		$broadcast_phone_numbers = $row["broadcast_phone_numbers"];
		$broadcast_destination_data = $row["broadcast_destination_data"];
		$broadcast_avmd = $row["broadcast_avmd"];
		$broadcast_accountcode = $row["broadcast_accountcode"] ?? $_SESSION['domain_name'];
	}

//set the schedule time
	$sched_seconds = (isset($broadcast_start_time) && is_numeric($broadcast_start_time)) ? $broadcast_start_time : 3;

//remove unsafe characters from the name
	$broadcast_name = str_replace([" ", "'"], "", $broadcast_name);

//create the event socket connection
	$fp = event_socket::create();

//get information over event socket
	if (!$fp) {
		require_once "resources/header.php";
		echo "<div align='center'>Connection to Event Socket failed.<br /></div>";
		require_once "resources/footer.php";
		exit;
	}

//show the header
	require_once "resources/header.php";

//send the call broadcast
	if (!empty($broadcast_phone_numbers)) {
		$broadcast_phone_number_array = explode("\n", $broadcast_phone_numbers);
		$count = 1;
		
		foreach ($broadcast_phone_number_array as $tmp_value) {
			//clean and parse the phone number
			$tmp_value = str_replace(";", "|", $tmp_value);
			$phone_number = preg_replace('{\D}', '', explode("|", $tmp_value)[0]);

			if (is_numeric($phone_number)) {
				//prepare the channel variables
				$channel_variables = [
					"ignore_early_media=true",
					"origination_caller_id_name='$broadcast_caller_id_name'",  // Outbound caller ID
					"origination_caller_id_number=$broadcast_caller_id_number", // Outbound caller ID
					"effective_caller_id_number=$phone_number",  // What the destination sees
					"effective_caller_id_name=$phone_number",    // What the destination sees
					"domain_uuid=".$_SESSION['domain_uuid'],
					"domain_name=".$_SESSION['domain_name'],
					"accountcode='$broadcast_accountcode'"
				];
				
				//add AVMD if enabled
				if ($broadcast_avmd == "true") {
					$channel_variables[] = "execute_on_answer='avmd start'";
				}
				
				//create the originate string
				$channel_vars_string = implode(",", $channel_variables);
				$origination_url = "{".$channel_vars_string."}loopback/$phone_number/".$_SESSION['domain_name'];
				$context = $_SESSION['domain_name'];
				
				//build the command
				$cmd = "bgapi sched_api +".$sched_seconds." ".$call_broadcast_uuid." bgapi originate ".$origination_url." ".$broadcast_destination_data." XML $context";
				
				//execute the command
				$response = event_socket::command($cmd);
				
				//throttle calls if concurrent limit is set
				if (!empty($broadcast_concurrent_limit) && $broadcast_concurrent_limit == $count) {
					$sched_seconds += $broadcast_timeout;
					$count = 0;
				}
				
				$count++;
			}
		}
		
		//show success message
		echo "<div align='center'>";
		echo "<table width='50%'>";
		echo "<tr><th align='left'>Message</th></tr>";
		echo "<tr><td class='row_style1' align='center'>";
		echo "<strong>".$text['label-call-broadcast']." ".$broadcast_name." ".$text['label-has-been']."</strong>";
		
		if (permission_exists('call_active_view')) {
			echo "<br /><br /><table width='100%'><tr><td align='center'>";
			echo "<a href='".PROJECT_PATH."/app/calls_active/calls_active.php'>".$text['label-view-calls']."</a>";
			echo "</td></tr></table>";
		}
		
		echo "</td></tr></table></div>";
	}

//show the footer
	require_once "resources/footer.php";
?>