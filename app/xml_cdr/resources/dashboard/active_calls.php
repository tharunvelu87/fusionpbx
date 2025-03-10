<?php
// Initialize the Event Socket connection
function get_active_calls() {
    $esl = new event_socket;
    $fp = $esl->event_socket_create('127.0.0.1', '8021', 'ClueCon'); // Use your FreeSWITCH ESL settings

    if (!$fp) {
        echo "Error: Connection to FreeSWITCH failed.";
        exit;
    }

    // Fetch active calls with 'show channels'
    $response = $esl->event_socket_request($fp, "api show channels as json");
    $data = json_decode($response, true);

    return $data['rows']; // Return the rows of active calls
}

// Get active calls
$active_calls = get_active_calls();

// Check if there are active calls
if (empty($active_calls)) {
    echo "<p>No active calls at this time.</p>";
} else {
    echo "<table>";
    echo "<tr><th>Call UUID</th><th>Caller</th><th>Callee</th><th>Duration</th></tr>";

    // Display active call details
    foreach ($active_calls as $call) {
        echo "<tr>";
        echo "<td>".$call['uuid']."</td>";
        echo "<td>".$call['caller_id_name']."</td>";
        echo "<td>".$call['destination']."</td>";
        echo "<td>".$call['duration']."</td>";
        echo "</tr>";
    }

    echo "</table>";
}
?>
