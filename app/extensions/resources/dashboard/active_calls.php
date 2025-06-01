<?php
/*
  app/extensions/resources/dashboard/active_calls.php
  FusionPBX Dashboard Widget: Live Active Calls
*/

require_once dirname(__DIR__,4) . '/resources/require.php';
require_once 'resources/check_auth.php';

if (!permission_exists('call_active_view')) {
    echo 'access denied';
    exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ———————————————————————————————————————————————————————————
// Pull & choose one leg per call_uuid
function get_active_calls() {
    $es = event_socket::create();
    if (!$es->is_connected()) return [];

    $json = trim($es->api('show channels as json'));
    $data = json_decode($json, true);
    $rows = $data['rows'] ?? [];

    // group legs by call_uuid (fallback uuid)
    $groups = [];
    foreach ($rows as $r) {
        if (!in_array($r['direction'], ['inbound','outbound'])) continue;
        $key = $r['call_uuid'] ?: $r['uuid'];
        $groups[$key][] = $r;
    }

    $out = [];
    $domain = $_SESSION['domain_name'];
    $me     = $_SESSION['user']['extension'][0]['user'] ?? '';
    $show_all = permission_exists('call_active_all');

    foreach ($groups as $legs) {
        // domain filter
        $keep = false;
        foreach ($legs as $l) {
            $ctx = $l['context'] ?: $l['presence_id'];
            $dom = strpos($ctx,'@')!==false?explode('@',$ctx)[1]:$ctx;
            if ($show_all || $dom === $domain) { $keep = true; break; }
        }
        if (!$keep) continue;

        // drop the gateway inbound leg
        $legs = array_filter($legs, function($l){
            return !( $l['direction']==='inbound'
                   && stripos($l['application_data'],'sofia/gateway/')!==false );
        });
        if (empty($legs)) continue;

        // detect EARLY = ringing
        $ring = false;
        foreach ($legs as $l) {
            if ($l['callstate']==='EARLY') { $ring = true; break; }
        }

        // pick the leg
        if ($ring) {
            foreach ($legs as $l) {
                if ($l['callstate']==='EARLY') { $leg = $l; break; }
            }
        }
        else {
            // prefer inbound ACTIVE
            $leg = null;
            foreach ($legs as $l) {
                if ($l['direction']==='inbound' && $l['callstate']==='ACTIVE') {
                    $leg = $l; break;
                }
            }
            // else outbound ACTIVE
            if (!$leg) {
                foreach ($legs as $l) {
                    if ($l['direction']==='outbound' && $l['callstate']==='ACTIVE') {
                        $leg = $l; break;
                    }
                }
            }
            // fallback first
            if (!$leg) {
                $leg = reset($legs);
            }
        }

        // status & icon
        if ($ring) {
            $status = 'Ringing';
            $icon   = 'fas fa-bell blink yellow';
        }
        elseif ($leg['callstate']==='ACTIVE') {
            $status = 'Connected';
            $icon   = ($leg['direction']==='outbound')
                    ? 'fas fa-arrow-up blue'
                    : 'fas fa-arrow-down green';
        }
        else {
            // must be outbound but not ACTIVE
            $status = 'Dialed';
            $icon   = 'fas fa-arrow-up blue';
        }

        // caller
        $cid = $leg['cid_num'] ?: $leg['initial_cid_num'];
        if (!$ring && $leg['direction']==='outbound' && $me) {
            $cid = $me;
        }

        // destination
        if ($ring || $leg['direction']==='outbound') {
            $dst = $leg['dest'] ?: $leg['initial_dest'];
        } else {
            if (preg_match_all("/\]sofia\/[^\/]+\/sip:([^@]+)/", $leg['application_data'], $m)) {
                $dst = end($m[1]);
            }
            else {
                $dst = $leg['dest'];
            }
        }

        $out[] = compact('icon','cid','dst','status');
    }

    return $out;
}

// ———————————————————————————————————————————————————————————
// AJAX endpoint
if (!empty($_GET['ajax'])) {
    header('Content-Type:application/json');
    $calls = get_active_calls();
    $count = count($calls);
    $rows_html = '';
    foreach ($calls as $c) {
        $rows_html .= '<tr>'
            ."<td style='text-align:center;'><i class='{$c['icon']}'></i></td>"
            ."<td class='hud_text'>{$c['cid']}</td>"
            ."<td class='hud_text'>{$c['dst']}</td>"
            ."<td class='hud_text'>{$c['status']}</td>"
            .'</tr>';
    }
    if ($rows_html === '') {
        $rows_html = "<tr><td colspan='4' class='hud_text' style='text-align:center;color:#888'>"
                   ."No active calls</td></tr>";
    }
    echo json_encode(['count'=>$count,'rows'=>$rows_html]);
    exit;
}

// ———————————————————————————————————————————————————————————
// toggle for expand/collapse
$toggle = ($dashboard_details_state === 'disabled')
    ? ''
    : " onclick=\"\$('#hud_active_calls_details').slideToggle('fast');"
      ."toggle_grid_row_end('{$dashboard_name}');refreshActiveCalls();\"";
?>
<div class="hud_box" id="active_calls_widget">

  <!-- Collapsed HUD -->
  <div class="hud_content"<?php echo $toggle;?>>
    <span class="hud_title">
      <?php echo $text['label-active_calls'] ?? 'Active Calls';?>
    </span>
    <div style="position:relative;display:inline-block;margin:0.5rem 0;">
      <span class="hud_stat">
        <i class="fas <?php echo htmlspecialchars($dashboard_icon ?: 'fa-phone');?>"></i>
      </span>
      <span id="active_calls_count" style="
        position:absolute;top:22px;left:24px;
        background:<?php echo $settings->get('theme','dashboard_number_background_color')?:'#ea4c46';?>;
        color:<?php echo $settings->get('theme','dashboard_number_text_color')?:'#fff';?>;
        font-size:12px;font-weight:bold;padding:2px 6px;border-radius:10px;
      ">0</span>
    </div>
  </div>

  <?php if ($dashboard_details_state !== 'disabled'): ?>
    <div class="hud_details hud_box" id="hud_active_calls_details" style="display:none;padding:10px;">
      <table class="tr_hover" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <th class="hud_heading">&nbsp;</th>
          <th class="hud_heading">Caller</th>
          <th class="hud_heading">Destination</th>
          <th class="hud_heading">Status</th>
        </tr>
        <tbody id="active_calls_rows">
          <tr><td colspan="4" class="hud_text" style="text-align:center;color:#888">
            Loading…
          </td></tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <!-- single bottom expander bar -->
  <span class="hud_expander"<?php echo $toggle;?>>
    <span class="fas fa-ellipsis-h"></span>
  </span>

</div>

<style>
  .blink { animation: blinker 1s linear infinite; }
  @keyframes blinker {50%{opacity:0}}
  .yellow { color:#f1c40f }
  .green  { color:#2ecc71 }
  .blue   { color:#417ed3 }
  #active_calls_widget .tr_hover th,
  #active_calls_widget .tr_hover td {
    padding:4px 8px;
  }
  #active_calls_widget .tr_hover tr:nth-child(even) {
    background:#f9f9f9;
  }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(function($){
  const URL = '<?php echo PROJECT_PATH;?>'
            + '/app/extensions/resources/dashboard/active_calls.php?ajax=1';

  function refreshActiveCalls(){
    $.getJSON(URL)
     .done(data=>{
        $('#active_calls_count').text(data.count);
        $('#active_calls_rows').html(data.rows);
     })
     .fail(()=>{
        $('#active_calls_count').text('0');
        $('#active_calls_rows').html(
          "<tr><td colspan='4' class='hud_text' style='text-align:center;color:#888'>"
         +"Error loading</td></tr>"
        );
     });
  }

  // initial + every 5s
  refreshActiveCalls();
  setInterval(refreshActiveCalls, 5000);

  // also on expand/collapse
  $('#active_calls_widget').on('click','.hud_content,.hud_expander',
    refreshActiveCalls
  );
});
</script>
