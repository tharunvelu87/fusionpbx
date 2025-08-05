<?php
/*
  FusionPBX Dashboard Widget: Active Calls (Fixed to Use Configured Icon and Style)
*/

require_once dirname(__DIR__,4) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('call_active_view')) {
    echo 'access denied'; exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$dashboard_name = 'active_calls';
$dashboard_details_state = $_SESSION['dashboard_details_state'] ?? 'default';

$settings = new settings;

$dashboard_number_background_color = $settings->get('theme', 'dashboard_number_background_color') ?: '#EA4C46';
$dashboard_number_text_color = $settings->get('theme', 'dashboard_number_text_color') ?: '#FFFFFF';

try {
    $sql = "SELECT dashboard_icon FROM v_dashboard_blocks WHERE dashboard_name = :name LIMIT 1";
    $parameters['name'] = $dashboard_name;
    $dashboard_icon = $database->select($sql, $parameters, 'column') ?: 'fa-phone-volume';
    unset($parameters);
} catch (Exception $e) {
    $dashboard_icon = 'fa-phone-volume';
}

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
    $me     = $_SESSION['user']['extension'][0]['user'] ?? '';
    $out    = [];

    foreach ($groups as $legs) {
        $keep = false;
        foreach ($legs as $l) {
            $ctx = $l['context'] ?: $l['presence_id'];
            $dom = strpos($ctx,'@')!==false ? explode('@',$ctx)[1] : $ctx;
            if ($show_all || $dom === $domain) {
                $keep = true; break;
            }
        }
        if (!$keep) continue;

        $external_in = false;
        foreach ($legs as $l) {
            if ($l['direction'] === 'inbound'
             && stripos($l['application_data'],'sofia/gateway/') !== false) {
                $external_in = true;
                break;
            }
        }

        $sip_legs = array_filter($legs, function($l) {
            return !(
                $l['direction'] === 'inbound'
             && stripos($l['application_data'],'sofia/gateway/') !== false
            );
        });
        if (empty($sip_legs)) continue;

        $ring = false;
        foreach ($sip_legs as $l) {
            if ($l['callstate'] === 'EARLY') { $ring = true; break; }
        }

        if ($ring) {
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'EARLY') { $leg = $l; break; }
            }
        } else {
            $leg = null;
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'ACTIVE'
                 && stripos($l['application_data'],'sofia/internal/') !== false) {
                    $leg = $l; break;
                }
            }
            if (!$leg) {
                foreach ($sip_legs as $l) {
                    if ($l['callstate'] === 'ACTIVE') { $leg = $l; break; }
                }
            }
            if (!$leg) $leg = reset($sip_legs);
        }

        $status = $ring ? 'Ringing' : ($leg['callstate'] === 'ACTIVE' ? 'Connected' : 'Dialed');
        $icon   = $ring ? 'fas fa-bell blink yellow' :
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
            $sec = $ans>0 ? time() - $ans : time() - intval($leg['created_epoch'] ?? time());
            $h = floor($sec/3600);
            $m = floor(($sec%3600)/60);
            $s = $sec%60;
            return $h>0 ? sprintf('%02d:%02d:%02d',$h,$m,$s) : sprintf('%02d:%02d',$m,$s);
        })();

        $out[] = compact('icon','cid','dst','duration','status');
    }
    return $out;
}

if (!empty($_GET['ajax'])) {
    header('Content-Type:application/json');
    $show_all = permission_exists('call_active_all');
    $list  = get_active_calls($show_all);
    $count = count($list);
    $rows  = '';
    foreach ($list as $c) {
        $rows .= '<tr>'
               . "<td style='text-align:center;'><i class='{$c['icon']}'></i></td>"
               . "<td class='hud_text'>{$c['cid']}</td>"
               . "<td class='hud_text'>{$c['dst']}</td>"
               . "<td class='hud_text'>{$c['duration']}</td>"
               . "<td class='hud_text'>{$c['status']}</td>"
               . '</tr>';
    }
    if ($rows === '') {
        $rows = "<tr><td colspan='5' class='hud_text' style='text-align:center;color:#888;'>No active calls</td></tr>";
    }
    echo json_encode(['count'=>$count,'rows'=>$rows]);
    exit;
}

$toggle = ($dashboard_details_state==='disabled') ? '' : " onclick=\"$('#hud_active_calls_details').slideToggle('fast');toggle_grid_row_end('{$dashboard_name}');refreshActiveCalls();\"";
?>
<div class='hud_box' id='active_calls_widget'>
  <div class='hud_content'<?php echo $toggle; ?>>
    <span class='hud_title'><?php echo $text['label-active_calls'] ?? 'Active Calls'; ?></span>
    <div style='position: relative; display: inline-block;'>
      <span class='hud_stat'><i class="fas <?php echo $dashboard_icon; ?>"></i></span>
      <span id='active_calls_count' style="background-color: <?php echo $dashboard_number_background_color; ?>; color: <?php echo $dashboard_number_text_color; ?>; font-size: 12px; font-weight: bold; text-align: center; position: absolute; top: 23px; left: 24.5px; padding: 2px 7px; border-radius: 10px;">0</span>
    </div>
  </div>

  <?php if ($dashboard_details_state !== 'disabled'): ?>
  <div class='hud_details hud_box' id='hud_active_calls_details' style='display:<?php echo ($dashboard_details_state === 'expanded') ? '' : 'none'; ?>;'>
  <table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>
    <tr>
      <th class='hud_heading'>&nbsp;</th>
      <th class='hud_heading'>Caller</th>
      <th class='hud_heading'>Destination</th>
      <th class='hud_heading'>Duration</th>
      <th class='hud_heading'>Status</th>
    </tr>
    <tbody id='active_calls_rows'>
      <tr><td colspan='5' class='hud_text' style='text-align:center;color:#888;'>Loading…</td></tr>
    </tbody>
  </table>
</div>
  <?php endif; ?>
</div>

<style>
  .blink  { animation: blinker 1s linear infinite; }
  @keyframes blinker {50%{opacity:0}}
  .yellow { color:#f1c40f; }
  .green  { color:#2ecc71; }
  .blue   { color:#417ed3; }
</style>

<script>
jQuery(function($){
  const URL = '<?php echo PROJECT_PATH;?>/app/calls_active/resources/dashboard/active_calls.php?ajax=1';
  function refreshActiveCalls(){
    $.getJSON(URL, data=>{
      $('#active_calls_count').text(data.count);
      $('#active_calls_rows').html(data.rows);
    });
  }
  refreshActiveCalls();
  setInterval(refreshActiveCalls,5000);
});
</script>
