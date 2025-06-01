<?php
/*
  app/extensions/resources/dashboard/active_calls.php
  FusionPBX Dashboard Widget: Live Active Calls
  ———————————————————————————————————————————————————————————
  • 5 columns: Icon | Caller | Destination | Duration | Status
  • Colored icons (yellow/green/blue)
  • Duration from FreeSWITCH JSON or answered_epoch fallback
  • Inbound answered → picks first numeric presence_id leg
  • Domain toggle
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

    // Fetch current channels
    $rows = json_decode(trim($es->api('show channels as json')), true)['rows'] ?? [];
    // Group legs by call_uuid (or uuid fallback)
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
        // Domain filter
        $keep = false;
        foreach ($legs as $l) {
            $ctx = $l['context'] ?: $l['presence_id'];
            $dom = strpos($ctx,'@')!==false ? explode('@',$ctx)[1] : $ctx;
            if ($show_all || $dom === $domain) {
                $keep = true; break;
            }
        }
        if (!$keep) continue;

        // Detect external inbound trunk
        $external_in = false;
        foreach ($legs as $l) {
            if ($l['direction'] === 'inbound'
             && stripos($l['application_data'],'sofia/gateway/') !== false) {
                $external_in = true;
                break;
            }
        }

        // Drop gateway legs
        $sip_legs = array_filter($legs, function($l) {
            return !(
                $l['direction'] === 'inbound'
             && stripos($l['application_data'],'sofia/gateway/') !== false
            );
        });
        if (empty($sip_legs)) continue;

        // Detect ringing
        $ring = false;
        foreach ($sip_legs as $l) {
            if ($l['callstate'] === 'EARLY') { $ring = true; break; }
        }

        // Pick a leg for status/duration (prefer internal ACTIVE)
        if ($ring) {
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'EARLY') { $leg = $l; break; }
            }
        }
        else {
            $leg = null;
            // internal ACTIVE first
            foreach ($sip_legs as $l) {
                if ($l['callstate'] === 'ACTIVE'
                 && stripos($l['application_data'],'sofia/internal/') !== false) {
                    $leg = $l; break;
                }
            }
            // any ACTIVE fallback
            if (!$leg) {
                foreach ($sip_legs as $l) {
                    if ($l['callstate'] === 'ACTIVE') { $leg = $l; break; }
                }
            }
            // final fallback
            if (!$leg) $leg = reset($sip_legs);
        }

        // Status & coloured icon
        if ($ring) {
            $status = 'Ringing';
            $icon   = 'fas fa-bell blink yellow';
        }
        elseif ($leg['callstate'] === 'ACTIVE') {
            $status = 'Connected';
            $icon   = ($leg['direction'] === 'outbound')
                    ? 'fas fa-arrow-up blue'
                    : 'fas fa-arrow-down green';
        }
        else {
            $status = 'Dialed';
            $icon   = 'fas fa-arrow-up blue';
        }

        // Find external leg (for Caller & dialed number)
        $external = reset(array_filter($sip_legs, function($l) use($domain) {
            return strpos($l['presence_id'], "@{$domain}") === false;
        })) ?: $leg;

        // Caller
        $cid = $external['cid_num'] ?: $external['initial_cid_num'];
        if (!$ring && $leg['direction'] === 'outbound' && $me) {
            $cid = $me;
        }

        // Destination
        if ($external_in && $leg['callstate'] === 'ACTIVE') {
            // Try to pull numeric extension from presence_id of any leg
            $dst = null;
            foreach ($sip_legs as $l2) {
                if (preg_match('/^(\d+)@/', $l2['presence_id'], $m)) {
                    $dst = $m[1];
                    break;
                }
            }
            // Fallback to application_data parse
            if (!$dst && preg_match('#sofia/internal/([^/@]+)#', $leg['application_data'], $m2)) {
                $dst = $m2[1];
            }
            // Last fallback to dialed
            if (!$dst) {
                $dst = $external['dest'] ?: $external['initial_dest'];
            }
        }
        else {
            // Outbound or ringing → dialed number
            $dst = $external['dest'] ?: $external['initial_dest'];
        }

        // Duration: use FS JSON field or compute
        if (!empty($leg['duration'])) {
            $duration = $leg['duration'];
        }
        else {
            $ans = intval($leg['answered_epoch'] ?? 0);
            $sec = $ans>0
                 ? time() - $ans
                 : time() - intval($leg['created_epoch'] ?? time());
            $h = floor($sec/3600);
            $m = floor(($sec%3600)/60);
            $s = $sec%60;
            $duration = $h>0
                      ? sprintf('%02d:%02d:%02d',$h,$m,$s)
                      : sprintf('%02d:%02d',$m,$s);
        }

        $out[] = compact('icon','cid','dst','duration','status');
    }

    return $out;
}

// AJAX endpoint
if (!empty($_GET['ajax'])) {
    header('Content-Type:application/json');
    $show_all = permission_exists('call_active_all')
              && !empty($_GET['show_all']);
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

// Render widget
$toggle = ($dashboard_details_state==='disabled')
    ? ''
    : " onclick=\"\$('#hud_active_calls_details').slideToggle('fast');"
      ."toggle_grid_row_end('{$dashboard_name}');refreshActiveCalls();\"";
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
      <?php if (permission_exists('call_active_all')): ?>
        <div style="text-align:right;margin-bottom:5px;">
          <button id="btn_toggle_all" class="btn">
            Show <?php echo !empty($_GET['show_all']) ? 'Current Domain' : 'All Domains';?>
          </button>
        </div>
      <?php endif; ?>
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
  let showAll = false;
  function refreshActiveCalls(){
    $.getJSON(URL + (showAll ? '&show_all=1' : ''), data=>{
      $('#active_calls_count').text(data.count);
      $('#active_calls_rows').html(data.rows);
    });
  }
  refreshActiveCalls();
  setInterval(refreshActiveCalls,1000);
  $('#active_calls_widget').on('click','.hud_content,.hud_expander',refreshActiveCalls);
  $('#btn_toggle_all').click(e=>{
    e.stopPropagation();
    showAll = !showAll;
    $(e.target).text(showAll ? 'Show Current Domain' : 'Show All Domains');
    refreshActiveCalls();
  });
});
</script>
