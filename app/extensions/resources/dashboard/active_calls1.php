<?php
/*
  FusionPBX Dashboard Widget: Live Active Calls (Fixed Version)
*/

require_once dirname(__DIR__,4) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('call_active_view')) {
    echo 'access denied'; exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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

        if ($ring) {
            $status = 'Ringing';
            $icon   = 'fas fa-bell blink yellow';
        } elseif ($leg['callstate'] === 'ACTIVE') {
            $status = 'Connected';
            $icon   = ($leg['direction'] === 'outbound')
                    ? 'fas fa-arrow-up blue'
                    : 'fas fa-arrow-down green';
        } else {
            $status = 'Dialed';
            $icon   = 'fas fa-arrow-up blue';
        }

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

        if (!empty($leg['duration'])) {
            $duration = $leg['duration'];
        } else {
            $ans = intval($leg['answered_epoch'] ?? 0);
            $sec = $ans>0 ? time() - $ans : time() - intval($leg['created_epoch'] ?? time());
            $h = floor($sec/3600);
            $m = floor(($sec%3600)/60);
            $s = $sec%60;
            $duration = $h>0 ? sprintf('%02d:%02d:%02d',$h,$m,$s) : sprintf('%02d:%02d',$m,$s);
        }

        $out[] = compact('icon','cid','dst','duration','status');
    }

    return $out;
}

// AJAX endpoint
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

$toggle = ($dashboard_details_state==='disabled')
    ? ''
    : " onclick=\"$('#hud_active_calls_details').slideToggle('fast');toggle_grid_row_end('{$dashboard_name}');refreshActiveCalls();\"";
?>
<div class="hud_box" id="active_calls_widget">
  <div class="hud_content"<?php echo $toggle;?>>
    <span class="hud_title"><?php echo $text['label-active_calls'] ?? 'Active Calls';?></span>
    <div style="position:relative;display:inline-block;margin:0.5rem 0;">
      <span class="hud_stat"><i class="<?php echo htmlspecialchars($dashboard_icon?:'fas fa-phone');?>"></i></span>
      <span id="active_calls_count" style="
        position:absolute;top:22px;left:24px;
        background:<?php echo $settings->get('theme','dashboard_number_background_color')?:'#ea4c46';?>;
        color:<?php echo $settings->get('theme','dashboard_number_text_color')?:'#fff';?>;
        padding:2px 6px;border-radius:10px;font-size:12px;font-weight:bold;
      ">0</span>
    </div>
  </div>

  <?php if ($dashboard_details_state!=='disabled'): ?>
    <div class="hud_details hud_box" id="hud_active_calls_details" style="display:none;padding:10px;">
      <table class="tr_hover" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <th class="hud_heading">&nbsp;</th>
          <th class="hud_heading">Caller</th>
          <th class="hud_heading">Destination</th>
          <th class="hud_heading">Duration</th>
          <th class="hud_heading">Status</th>
        </tr>
        <tbody id="active_calls_rows">
          <tr>
            <td colspan="5" class="hud_text" style="text-align:center;color:#888;">Loading…</td>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <span class="hud_expander"<?php echo $toggle;?>><span class="fas fa-ellipsis-h"></span></span>
</div>

<style>
  .blink  { animation: blinker 1s linear infinite; }
  @keyframes blinker {50%{opacity:0}}
  .yellow { color:#f1c40f; }
  .green  { color:#2ecc71; }
  .blue   { color:#417ed3; }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(function($){
  const URL = '<?php echo PROJECT_PATH;?>/app/extensions/resources/dashboard/active_calls1.php?ajax=1';
  function refreshActiveCalls(){
    $.getJSON(URL, data=>{
      $('#active_calls_count').text(data.count);
      $('#active_calls_rows').html(data.rows);
    });
  }
  refreshActiveCalls();
  setInterval(refreshActiveCalls,1000);
  $('#active_calls_widget').on('click','.hud_content,.hud_expander',refreshActiveCalls);
});
</script>
