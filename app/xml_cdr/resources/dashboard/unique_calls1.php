<?php
// unique_received_calls.php
// FusionPBX Dashboard Widget: Displays unique (inbound) received calls, showing only the record with the longest duration per caller.
// The widget looks like the Recent Calls widget.
// Version: MPL 1.1

// ----------------------------------------------------------
// 1. Includes and Permissions
// ----------------------------------------------------------
require_once dirname(__DIR__, 4) . "/resources/require.php";
require_once "resources/check_auth.php";

if (!permission_exists('xml_cdr_view')) {
    echo "access denied";
    exit;
}

// ----------------------------------------------------------
// 2. Multi-Lingual Support
// ----------------------------------------------------------
$language = new text;
$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

// ----------------------------------------------------------
// 3. Dashboard Parameters and Time Format
// ----------------------------------------------------------
$unique_limit = isset($selected_blocks) && is_array($selected_blocks) && in_array('counts', $selected_blocks) ? 500 : 500;

$sql_time_format = 'DD Mon HH12:MI am';
if (!empty($_SESSION['domain']['time_format']['text'])) {
    $sql_time_format = ($_SESSION['domain']['time_format']['text'] == '12h')
        ? "DD Mon HH12:MI am"
        : "DD Mon HH24:MI";
}

// ----------------------------------------------------------
// 4. Query for Unique Inbound Calls (Longest Call per Caller)
// ----------------------------------------------------------
// This query selects inbound calls from the last 24 hours,
// partitions by caller_id_number and uses the longest call (by billsec) for each caller.
$sql = "
SELECT * FROM (
    SELECT
        status,
        direction,
        start_stamp,
        to_char(timezone(:time_zone, start_stamp), '".$sql_time_format."') AS start_date_time,
        caller_id_name,
        caller_id_number,
        destination_number,
        answer_stamp,
        bridge_uuid,
        sip_hangup_disposition,
        billsec,
        row_number() OVER (PARTITION BY caller_id_number ORDER BY billsec DESC) AS rn
    FROM v_xml_cdr
    WHERE domain_uuid = :domain_uuid
      AND direction = 'inbound'
      AND hangup_cause <> 'LOSE_RACE'
      AND start_epoch > " . (time() - 86400) . "
) AS sub
WHERE rn = 1
ORDER BY start_stamp DESC
LIMIT :unique_limit
";

$parameters = array();
$parameters['unique_limit'] = $unique_limit;
$parameters['time_zone']    = isset($_SESSION['domain']['time_zone']['name'])
                                ? $_SESSION['domain']['time_zone']['name']
                                : date_default_timezone_get();
$parameters['domain_uuid']  = $_SESSION['domain_uuid'];

if (!isset($database)) { $database = new database; }
$result = $database->select($sql, $parameters, 'all');
$num_unique = !empty($result) ? sizeof($result) : 0;

// ----------------------------------------------------------
// 5. Set Up Row Styles and Theme Icon Check
// ----------------------------------------------------------
$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

$theme_image_path = PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/";
$theme_cdr_images_exist = (
    file_exists($theme_image_path."icon_cdr_inbound_answered.png") &&
    file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
    file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
    file_exists($theme_image_path."icon_cdr_inbound_failed.png") &&
    file_exists($theme_image_path."icon_cdr_outbound_answered.png") &&
    file_exists($theme_image_path."icon_cdr_outbound_cancelled.png") &&
    file_exists($theme_image_path."icon_cdr_outbound_failed.png") &&
    file_exists($theme_image_path."icon_cdr_local_answered.png") &&
    file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
    file_exists($theme_image_path."icon_cdr_local_cancelled.png") &&
    file_exists($theme_image_path."icon_cdr_local_failed.png")
) ? true : false;

// ----------------------------------------------------------
// 6. Render Widget Header (Mimics Recent Calls Widget)
// ----------------------------------------------------------
echo "<div class='hud_box'>\n";
echo "<div class='hud_content' " . ($dashboard_details_state == "disabled" ? "" : "onclick=\"$('#hud_unique_calls_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"") . " style='cursor: pointer;'>\n";
echo "  <span class='hud_title'><a onclick=\"document.location.href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php';\">" . ($text['label-unique_received_calls'] ?? 'Unique Calls') . "</a></span>\n";

// Dashboard display: show doughnut, number, or icon as configured.
if ($dashboard_chart_type == "doughnut") {
    ?>
    <div class='hud_chart'><canvas id='unique_calls_chart'></canvas></div>
    <script>
        const unique_calls_chart = new Chart(
            document.getElementById('unique_calls_chart').getContext('2d'),
            {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: ['<?php echo $num_unique; ?>', 0.00001],
                        backgroundColor: [
                            '<?php echo ($settings->get('theme', 'dashboard_recent_calls_chart_main_color') ?? '#2a9df4'); ?>',
                            '<?php echo ($settings->get('theme', 'dashboard_recent_calls_chart_sub_color') ?? '#d4d4d4'); ?>'
                        ],
                        borderColor: '<?php echo $settings->get('theme', 'dashboard_chart_border_color'); ?>',
                        borderWidth: '<?php echo $settings->get('theme', 'dashboard_chart_border_width'); ?>'
                    }]
                },
                options: {
                    plugins: {
                        chart_number: {
                            text: '<?php echo $num_unique; ?>'
                        }
                    }
                },
                plugins: [{
                    id: 'chart_number',
                    beforeDraw(chart, args, options){
                        const {ctx, chartArea: {top, right, bottom, left, width, height} } = chart;
                        ctx.font = chart_text_size + ' ' + chart_text_font;
                        ctx.textBaseline = 'middle';
                        ctx.textAlign = 'center';
                        ctx.fillStyle = '<?php echo $dashboard_number_text_color; ?>';
                        ctx.fillText(options.text, width / 2, top + (height / 2));
                        ctx.save();
                    }
                }]
            }
        );
    </script>
    <?php
}

if (!isset($dashboard_chart_type) || $dashboard_chart_type == "number") {
    echo "<span class='hud_stat'>".$num_unique."</span>";
}

if (!isset($dashboard_chart_type) || $dashboard_chart_type == "icon") {
    echo "<div class='hud_content'>\n";
    echo "  <div style='position: relative; display: inline-block;'>\n";
    echo "      <span class='hud_stat'><i class=\"fas ".$dashboard_icon." \"></i></span>\n";
    echo "      <span style=\"background-color: ".(!empty($dashboard_number_background_color) ? $dashboard_number_background_color : '#417ed3')."; color: ".(!empty($dashboard_number_text_color) ? $dashboard_number_text_color : '#ffffff')."; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 23px; left: 24.5px; padding: 2px 7px 1px 7px; border-radius: 10px; white-space: nowrap;\">".$num_unique."</span>\n";
    echo "  </div>\n";
    echo "</div>";
}
echo "</div>\n";

// ----------------------------------------------------------
// 7. Render the Details List (Table) with Short Titles and Icon
// ----------------------------------------------------------
if ($dashboard_details_state != 'disabled') {
    echo "<div class='hud_details hud_box' id='hud_unique_calls_details'>";
    echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
    echo "<tr>\n";
    if ($num_unique > 0) {
        echo "<th class='hud_heading'>&nbsp;</th>\n";
    }
    echo "<th class='hud_heading' width='100%'>" . ($text['label-caller'] ?? 'Caller') . "</th>\n";
    echo "<th class='hud_heading'>" . ($text['label-duration'] ?? 'Duration') . "</th>\n";
    echo "<th class='hud_heading'>" . ($text['label-date_time'] ?? 'Time') . "</th>\n";
    echo "</tr>\n";

    if ($num_unique > 0) {
        foreach ($result as $index => $row) {
            // Format start date and time.
            $start_date_time = str_replace('/0','/', ltrim($row['start_date_time'], '0'));
            if (!empty($_SESSION['domain']['time_format']) && $_SESSION['domain']['time_format']['text'] == '12h') {
                $start_date_time = str_replace(' 0',' ', $start_date_time);
            }
            // Determine the caller for display.
            $caller_display = !empty($row['caller_id_name']) ? $row['caller_id_name'] : $row['caller_id_number'];
            
            // Convert call duration from seconds to minutes.
            $billsec = (float)$row['billsec'];
            $minutes = $billsec / 60;
            if ($billsec < 60) {
                $duration_display = number_format($minutes, 1) . " min";
            } else {
                $duration_display = round($minutes) . " min";
            }
            
            // Set up click-to-call variables if permission exists.
            if (permission_exists('click_to_call_call')) {
                $tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
                    "?src_cid_name=" . urlencode($caller_display) .
                    "&src_cid_number=" . urlencode($row['caller_id_number']) .
                    "&dest_cid_name=" . urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name'] ?? '') .
                    "&dest_cid_number=" . urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number'] ?? '') .
                    "&src=" . urlencode($_SESSION['user']['extension'][0]['user'] ?? '') .
                    "&dest=" . urlencode($row['caller_id_number']) .
                    "&rec=" . (filter_var($_SESSION['click_to_call']['record']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? 'true' : 'false') .
                    "&ringback=" . (isset($_SESSION['click_to_call']['ringback']['text']) ? $_SESSION['click_to_call']['ringback']['text'] : "us-ring") .
                    "&auto_answer=" . (filter_var($_SESSION['click_to_call']['auto_answer']['boolean'] ?? false, FILTER_VALIDATE_BOOL) ? 'true' : 'false') .
                    "');\" style='cursor: pointer;'";
            }
            
            echo "<tr " . ($tr_link ?? "") . ">\n";
            // Icon cell
            echo "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
            if ($theme_cdr_images_exist) {
                $call_result = $row['status'];
                if (isset($row['direction'])) {
                    echo "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$row['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]."'>\n";
                }
            }
            echo "</td>\n";
            
            // Caller cell.
            echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'><a href='javascript:void(0);' " . (!empty($caller_display) ? "title=\"".$caller_display."\"" : "") . ">" . $caller_display . "</a></td>\n";
            
            // Duration cell (in minutes).
            echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$duration_display."</td>\n";
            
            // Date/Time cell.
            echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$start_date_time."</td>\n";
            echo "</tr>\n";
            $c = ($c) ? 0 : 1;
        }
    } else {
        echo "<tr><td colspan='4'>No calls found</td></tr>\n";
    }
    unset($sql, $parameters, $result, $num_unique, $index, $row);
    
    echo "</table>\n";
    echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php'>".($text['label-view_all'] ?? 'View All')."</a></span>\n";
    echo "</div>";
    
    echo "<span class='hud_expander' onclick=\"$('#hud_unique_calls_details').slideToggle('fast'); toggle_grid_row_end('".$dashboard_name."')\"><span class='fas fa-ellipsis-h'></span></span>";
}
echo "</div>\n";
?>
