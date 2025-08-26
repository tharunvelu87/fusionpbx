<?php
/*
  FusionPBX Dashboard Widget: Active Calls
  Fixed to use standard FusionPBX dashboard configuration
*/

//includes files
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once 'resources/check_auth.php';

//check permissions
if (!permission_exists('call_active_view')) {
    echo 'access denied'; 
    exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get($_SESSION['domain']['language']['code'] ?? null, 'app/calls_active');

//set default text if not in language file
if (empty($text['label-active_calls'])) {
    $text['label-active_calls'] = 'Active Calls';
}
if (empty($text['label-caller'])) {
    $text['label-caller'] = 'Caller';
}
if (empty($text['label-destination'])) {
    $text['label-destination'] = 'Destination';
}
if (empty($text['label-duration'])) {
    $text['label-duration'] = 'Duration';
}
if (empty($text['label-status'])) {
    $text['label-status'] = 'Status';
}

//get active calls function
function get_active_calls($show_all = false) {
    $es = event_socket::create();
    if (!$es || !$es->is_connected()) return [];

    $rows = json_decode(trim($es->api('show channels as json')), true)['rows'] ?? [];
    $groups = [];
    foreach ($rows as $r) {
        if (!in_array($r['direction'], ['inbound','outbound'])) continue;
        $key = $r['call_uuid'] ?: $r['uuid'];
        $groups[$key][] = $r;
    }

    $domain = $_SESSION['domain_name'];
    $me = $_SESSION['user']['extension'][0]['user'] ?? '';
    $out = [];

    foreach ($groups as $legs) {
        $keep = false;
        foreach ($legs as $l) {
            $ctx = $l['context'] ?: $l['presence_id'];
            $dom = strpos($ctx,'@') !== false ? explode('@', $ctx)[1] : $ctx;
            if ($show_all || $dom === $domain) {
                $keep = true; 
                break;
            }
        }
        if (!$keep) continue;

        $external_in = false;
        foreach ($legs as $l) {
            if ($l['direction'] === 'inbound' && stripos($l['application_data'], 'sofia/gateway/') !== false) {
                $external_in = true;
                break;
            }
        }

        $sip_legs = array_filter($legs, function($l) {
            return !(
                $l['direction'] === 'inbound' && stripos($l['application_data'], 'sofia/gateway/') !== false
            );
        });
        if (empty($sip_legs)) continue;

        $ring = false;
        foreach ($sip_legs as $l) {
            if ($l['callstate'] === 'EARLY') { 
                $ring = true; 
                break; 
            }
        }

        if ($ring) {
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'EARLY') { 
                    $leg = $l; 
                    break; 
                }
            }
        } else {
            $leg = null;
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'ACTIVE' && stripos($l['application_data'], 'sofia/internal/') !== false) {
                    $leg = $l; 
                    break;
                }
            }
            if (!$leg) {
                foreach ($sip_legs as $l) {
                    if ($l['callstate'] === 'ACTIVE') { 
                        $leg = $l; 
                        break; 
                    }
                }
            }
            if (!$leg) $leg = reset($sip_legs);
        }

        $status = $ring ? 'Ringing' : ($leg['callstate'] === 'ACTIVE' ? 'Connected' : 'Dialed');
        $icon = $ring ? 'fas fa-bell blink yellow' :
                ($leg['direction'] === 'outbound' ? 'fas fa-arrow-up blue' : 'fas fa-arrow-down green');

        $external = reset(array_filter($sip_legs, function($l) use($domain) {
            return strpos($l['presence_id'], "@{$domain}") === false;
        })) ?: $leg;

        $cid = $external['cid_num'] ?: $external['initial_cid_num'];
        if (!$ring && $leg['direction'] === 'outbound' && $me) {
            $cid = $me;
        }

        $dst = null;
        foreach ($sip_legs as $l2) {
            if (!empty($l2['answered_epoch']) && preg_match('/^(\d+)@/', $l2['presence_id'], $m)) {
                $dst = $m[1];
                break;
            }
        }
        if (!$dst) {
            foreach ($sip_legs as $l2) {
                if (preg_match('/^(\d+)@/', $l2['presence_id'], $m)) {
                    $dst = $m[1];
                    break;
                }
            }
        }
        if (!$dst && preg_match('#sofia/internal/([^/@]+)#', $leg['application_data'], $m2)) {
            $dst = $m2[1];
        }
        if (!$dst) {
            $dst = $external['dest'] ?: $external['initial_dest'];
        }

        $duration = !empty($leg['duration']) ? $leg['duration'] : (function() use ($leg) {
            $ans = intval($leg['answered_epoch'] ?? 0);
            $sec = $ans > 0 ? time() - $ans : time() - intval($leg['created_epoch'] ?? time());
            $h = floor($sec / 3600);
            $m = floor(($sec % 3600) / 60);
            $s = $sec % 60;
            return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
        })();

        $out[] = compact('icon', 'cid', 'dst', 'duration', 'status');
    }
    return $out;
}

//handle AJAX requests
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    $show_all = permission_exists('call_active_all');
    $list = get_active_calls($show_all);
    $count = count($list);
    $rows = '';
    
    foreach ($list as $c) {
        $rows .= '<tr>'
               . "<td style='text-align:center;'><i class='{$c['icon']}'></i></td>"
               . "<td class='hud_text'>" . escape($c['cid']) . "</td>"
               . "<td class='hud_text'>" . escape($c['dst']) . "</td>"
               . "<td class='hud_text'>" . escape($c['duration']) . "</td>"
               . "<td class='hud_text'>" . escape($c['status']) . "</td>"
               . '</tr>';
    }
    
    if ($rows === '') {
        $rows = "<tr><td colspan='5' class='hud_text' style='text-align:center;color:#888;'>No active calls</td></tr>";
    }
    
    echo json_encode(['count' => $count, 'rows' => $rows]);
    exit;
}

//use default icon if not set
if (empty($dashboard_icon)) {
    $dashboard_icon = 'fa-phone';
}

//set default colors if not configured
if (empty($dashboard_number_background_color)) {
    $dashboard_number_background_color = '#03c04a';
}
if (empty($dashboard_number_text_color)) {
    $dashboard_number_text_color = '#ffffff';
}

//widget HTML output
echo "<div class='hud_box' id='active_calls_widget'>\n";

//content section with count
$onclick = '';
if ($dashboard_details_state != 'disabled') {
    $onclick = " onclick=\"$('#hud_active_calls_details').slideToggle('fast'); toggle_grid_row_end('" . $dashboard_id . "'); refreshActiveCalls();\"";
}

echo "  <div class='hud_content'" . $onclick . ">\n";
echo "    <span class='hud_title'>" . $text['label-active_calls'] . "</span>\n";

//dashboard chart type handling
if ($dashboard_chart_type == 'icon' || empty($dashboard_chart_type)) {
    echo "    <div style='position: relative; display: inline-block;'>\n";
    echo "      <span class='hud_stat'><i class='fas " . $dashboard_icon . "'></i></span>\n";
    echo "      <span id='active_calls_count' style='background-color: " . $dashboard_number_background_color . "; ";
    echo "color: " . $dashboard_number_text_color . "; font-size: 12px; font-weight: bold; text-align: center; ";
    echo "position: absolute; top: 23px; left: 24.5px; padding: 2px 7px; border-radius: 10px;'>0</span>\n";
    echo "    </div>\n";
} else if ($dashboard_chart_type == 'number') {
    echo "    <span class='hud_stat' id='active_calls_count'>0</span>\n";
}

echo "  </div>\n";

//details section
if ($dashboard_details_state != 'disabled') {
    $display_style = ($dashboard_details_state == 'expanded') ? '' : 'display: none;';
    
    echo "  <div class='hud_details hud_box' id='hud_active_calls_details' style='" . $display_style . "'>\n";
    echo "    <table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
    echo "      <tr>\n";
    echo "        <th class='hud_heading' style='width: 30px;'>&nbsp;</th>\n";
    echo "        <th class='hud_heading'>" . $text['label-caller'] . "</th>\n";
    echo "        <th class='hud_heading'>" . $text['label-destination'] . "</th>\n";
    echo "        <th class='hud_heading'>" . $text['label-duration'] . "</th>\n";
    echo "        <th class='hud_heading'>" . $text['label-status'] . "</th>\n";
    echo "      </tr>\n";
    echo "      <tbody id='active_calls_rows'>\n";
    echo "        <tr><td colspan='5' class='hud_text' style='text-align:center;color:#888;'>Loading...</td></tr>\n";
    echo "      </tbody>\n";
    echo "    </table>\n";
    echo "  </div>\n";
}

echo "</div>\n";

?>

<style>
.blink { 
    animation: blinker 1s linear infinite; 
}
@keyframes blinker {
    50% { opacity: 0; }
}
.yellow { color: #f1c40f; }
.green { color: #2ecc71; }
.blue { color: #417ed3; }
</style>

<script>
jQuery(function($) {
    // Use relative path for AJAX calls
    const ajaxUrl =  '<?php echo PROJECT_PATH;?>/app/calls_active/resources/dashboard/active_calls.php?ajax=1';
    
    function refreshActiveCalls() {
        $.getJSON(ajaxUrl, function(data) {
            $('#active_calls_count').text(data.count);
            $('#active_calls_rows').html(data.rows);
        }).fail(function() {
            $('#active_calls_count').text('?');
            $('#active_calls_rows').html('<tr><td colspan="5" class="hud_text" style="text-align:center;color:#888;">Error loading calls</td></tr>');
        });
    }
    
    // Initial load
    refreshActiveCalls();
    
    // Refresh every 5 seconds
    setInterval(refreshActiveCalls, 5000);
});
</script>