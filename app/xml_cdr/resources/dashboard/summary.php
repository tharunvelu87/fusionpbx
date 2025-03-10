<?php
// Includes files
require_once dirname(__DIR__, 4) . "/resources/require.php";
require_once "resources/check_auth.php";

// Check permissions
if (permission_exists('destination_view')) {
    // Access granted
} else {
    echo "access denied";
    exit;
}

// Retrieve submitted data
$quick_select = $_REQUEST['quick_select'] ?? 3; // Default to 'today'

// Get the summary
$destination = new destinations;
$destination->domain_uuid = $_SESSION['domain_uuid'];
if (!empty($quick_select)) {
    $destination->quick_select = $quick_select;
}
$summary = $destination->destination_summary();

// Include the header
$document['title'] = "Destination Summary";
require_once "resources/header.php";

// CSS for dashboard layout
echo "<style>\n";
echo "    .dashboard-container { display: flex; flex-wrap: wrap; justify-content: space-around; margin-top: 20px; }\n";
echo "    .card-summary { background-color: #ffffff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); width: 300px; margin: 10px; transition: transform 0.2s; }\n";
echo "    .card-summary:hover { transform: scale(1.05); }\n";
echo "    .card-summary strong { font-weight: bold; color: #007bff; }\n";
echo "    .card-summary div { margin-bottom: 10px; font-size: 15px; }\n";
echo "    .form_grid { display: flex; justify-content: space-between; margin-bottom: 20px; }\n";
echo "    .form_set { flex: 1; margin-right: 10px; }\n";
echo "    .form_set:last-child { margin-right: 0; }\n";
echo "    .btn { background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }\n";
echo "    .btn:hover { background-color: #0056b3; }\n";
echo "</style>\n";

// Show date range form
echo "<form name='frm' id='frm' method='get'>\n";
echo "<div class='form_grid'>\n";

echo "    <div class='form_set'>\n";
echo "        <div class='label'>\n";
echo "            Quick Select:\n";
echo "        </div>\n";
echo "        <div class='field'>\n";
echo "            <select class='formfld' name='quick_select' id='quick_select' onchange=\"this.form.submit();\">\n";
echo "                <option value=''></option>\n";
echo "                <option value='1' ".($quick_select == 1 ? "selected='selected'" : null).">Last 7 Days</option>\n";
echo "                <option value='2' ".($quick_select == 2 ? "selected='selected'" : null).">Last Hour</option>\n";
echo "                <option value='3' ".($quick_select == 3 ? "selected='selected'" : null).">Today</option>\n";
echo "                <option value='4' ".($quick_select == 4 ? "selected='selected'" : null).">Yesterday</option>\n";
echo "                <option value='5' ".($quick_select == 5 ? "selected='selected'" : null).">This Week</option>\n";
echo "                <option value='6' ".($quick_select == 6 ? "selected='selected'" : null).">This Month</option>\n";
echo "                <option value='7' ".($quick_select == 7 ? "selected='selected'" : null).">This Year</option>\n";
echo "            </select>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "    <div class='form_set'>\n";
echo "        <div class='field'>\n";
echo "            <button type='submit' class='btn'>Search</button>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "</div>\n";
echo "</form>\n";

// Show dashboard
echo "<div class='dashboard-container'>\n";

// Summary cards
if (!empty($summary) && is_array($summary)) {
    foreach ($summary as $row) {
        echo "<div class='card-summary'>\n";
        echo "    <div><strong>Destination Number:</strong> ".escape($row['destination_number'])."</div>\n";
        echo "    <div><strong>Answered Calls:</strong> ".escape($row['answered_calls'])."</div>\n";
        echo "    <div><strong>Unique Callers:</strong> ".escape($row['unique_callers'])."</div>\n";
        echo "    <div><strong>Total Calls:</strong> ".escape($row['total_calls'])."</div>\n";
        echo "    <div><strong>Duration:</strong> ".(($row['total_seconds'] != '') ? format_hours($row['total_seconds']) : '0:00:00')."</div>\n";
        echo "</div>\n";
    }
}

echo "</div>\n";
?>
