<?php
/*
  FusionPBX Dashboard Widget: Dynamic Caller ID Selector
  Version: MPL 1.1
*/

require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once 'resources/check_auth.php';

// permission check
if (! permission_exists('extension_caller_id')) {
    echo 'access denied';
    exit;
}
// start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// helper: normalize to +1234567890
function format_number($n) {
    $d = preg_replace('/\D+/', '', $n);
    return ($d !== '') ? '+' . $d : 'None';
}

// pull your extension from session
$ext = $_SESSION['user']['extension'][0] ?? [];

// current values
$current_number = !empty($ext['outbound_caller_id_number'])
    ? format_number($ext['outbound_caller_id_number'])
    : 'None';
$current_name   = !empty($ext['outbound_caller_id_name'])
    ? $ext['outbound_caller_id_name']
    : 'None';

// AJAX handlers (upload_list & update_callerid) unchanged:
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    $resp   = [];

    if ($action === 'upload_list') {
        $raw       = trim($_POST['caller_ids'] ?? '');
        $use_name  = !empty($_POST['use_caller_id_name']);
        $name_text = trim($_POST['caller_id_name'] ?? '');
        $ids       = [];
        if ($raw !== '') {
            foreach (preg_split('/[\r\n,]+/', $raw) as $line) {
                $d = preg_replace('/\D+/', '', trim($line));
                if ($d !== '') $ids[] = '+' . $d;
            }
        }
        $_SESSION['caller_ids']         = $ids;
        $_SESSION['use_caller_id_name'] = $use_name;
        $_SESSION['caller_id_name']     = $name_text;
        $resp = ['status'=>'ok','caller_ids'=>$ids,'use_name'=>$use_name,'name'=>$name_text];
    }
    elseif ($action === 'update_callerid') {
        $raw = trim($_POST['caller_id'] ?? '');
        if ($raw === '') {
            $resp = ['status'=>'error','message'=>'Invalid caller ID'];
        } else {
            $d          = preg_replace('/\D+/', '', $raw);
            $num        = '+' . $d;
            $use_name   = !empty($_POST['use_caller_id_name']);
            $name_text  = $_SESSION['caller_id_name'] ?? '';
            $final_name = ($use_name && $name_text!=='') ? $name_text : $num;

            if (!empty($ext['extension_uuid'])) {
                $db = new database;
                try {
                    $db->execute(
                        "UPDATE v_extensions
                           SET outbound_caller_id_number = :num,
                               outbound_caller_id_name   = :name
                         WHERE extension_uuid = :uuid",
                        [':num'=>$num,':name'=>$final_name,':uuid'=>$ext['extension_uuid']]
                    );
                    if (!empty($ext['destination']) && !empty($ext['user_context'])) {
                        (new cache())->delete(
                            "directory:{$ext['destination']}@{$ext['user_context']}"
                        );
                    }
                    foreach ($_SESSION['user']['extension'] as & $e) {
                        if ($e['extension_uuid'] === $ext['extension_uuid']) {
                            $e['outbound_caller_id_number'] = $num;
                            $e['outbound_caller_id_name']   = $final_name;
                        }
                    }
                    $resp = ['status'=>'ok','selected'=>$num,'name'=>$final_name];
                }
                catch (Exception $ex) {
                    $resp = ['status'=>'error','message'=>'Database update failed'];
                }
            } else {
                $resp = ['status'=>'error','message'=>'Extension not found'];
            }
        }
    }

    echo json_encode($resp);
    exit;
}

// expand/collapse toggle
$toggle_attr = ($dashboard_details_state === 'disabled')
    ? ''
    : "onclick=\"\$('#caller_id_details').slideToggle('fast'); toggle_grid_row_end('{$dashboard_name}');\"";
?>
<div class="hud_box" id="caller_id_widget">

  <!-- Collapsed HUD -->
  <div class="hud_content" <?php echo $toggle_attr; ?>>
    <span class="hud_title">Caller ID</span>

    <div class="hud_icon_wrapper">
      <span class="hud_stat"><i class="fas fa-phone"></i></span>
      <span class="hud_badge"><?php echo htmlspecialchars($current_number); ?></span>
    </div>

    <span class="hud_substat">Name: <?php echo htmlspecialchars($current_name); ?></span>
    <span class="hud_expander"><span class="fas fa-ellipsis-h"></span></span>
  </div>

  <!-- Expanded Details -->
  <div class="hud_details hud_box" id="caller_id_details" style="display:none; padding:10px;">
    <form id="caller_form" onsubmit="return false;">
      <label>
        <input type="checkbox" id="use_caller_id_name"
          <?php echo ($current_name !== 'None') ? 'checked' : ''; ?>
        > Use Caller ID Name
      </label><br>
      <input type="text" id="caller_id_name" name="caller_id_name"
        placeholder="Caller Name"
        value="<?php echo htmlspecialchars($_SESSION['caller_id_name'] ?? ''); ?>"
        style="width:100%; margin:5px 0;"
      ><br>

      <h4>Available Caller IDs:</h4>
      <div id="caller_id_list">
        <?php if (!empty($_SESSION['caller_ids'])): ?>
          <?php foreach ($_SESSION['caller_ids'] as $cid): ?>
            <div class="caller-id-item" data-caller_id="<?php echo htmlspecialchars($cid); ?>">
              <?php echo htmlspecialchars($cid); ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No caller IDs uploaded.</p>
        <?php endif; ?>
      </div>

      <textarea id="caller_ids" name="caller_ids"
        style="width:100%; height:80px; font-family:monospace; margin-top:5px;"
      ><?php echo implode(', ', $_SESSION['caller_ids'] ?? []); ?></textarea><br>

      <?php
        echo button::create([
          'type'=>'button',
          'id'=>'upload_btn',
          'label'=>'Upload Caller IDs',
          'icon'=>$settings->get('theme','button_icon_save')
        ]);
      ?>
    </form>
  </div>

  <!-- Bottom expander bar -->
  <span class="hud_expander" <?php echo $toggle_attr; ?>>
    <span class="fas fa-ellipsis-h"></span>
  </span>

</div>

<style>
  /* Scope to this widget only */
  #caller_id_widget .hud_content {
    display:flex!important;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:1rem;
  }
  #caller_id_widget .hud_title {
    font-size:0.9rem;
    color:#666;
    margin-bottom:0.5rem;
    text-align:center;
  }
  #caller_id_widget .hud_icon_wrapper {
    position:relative;
    display:inline-block;
    margin-bottom:0.5rem;
  }
  #caller_id_widget .hud_badge {
    position:absolute;
    top:20px;
    right:-10px;
    background-color:#ea4c46;
    color:#fff;
    font-size:12px;
    font-weight:bold;
    padding:2px 6px;
    border-radius:10px;
    white-space:nowrap;
  }
  #caller_id_widget .hud_substat {
    font-size:0.85rem;
    color:#333;
    text-align:center;
    margin-top:-0.3rem;
  }
  #caller_id_widget .caller-id-item {
    padding:5px;
    margin:2px 0;
    border:1px solid #ddd;
    cursor:pointer;
  }
  #caller_id_widget .caller-id-item:hover {
    background:#f9f9f9;
  }
  #caller_id_widget .caller-id-item.selected {
    background:#e6f7ff;
  }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
jQuery(function($){
  const url = '<?php echo PROJECT_PATH; ?>/app/extensions/resources/dashboard/caller_id2.php';

  // upload list
  $('#caller_id_widget #upload_btn').click(function(){
    $.post(url + '?ajax=upload_list', {
      caller_ids: $('#caller_ids').val(),
      use_caller_id_name: $('#use_caller_id_name').is(':checked')?1:0,
      caller_id_name: $('#caller_id_name').val()
    }, resp=>{
      if (Array.isArray(resp.caller_ids)) {
        let h='';
        resp.caller_ids.forEach(c=> h+='<div class="caller-id-item" data-caller_id="'+c+'">'+c+'</div>');
        $('#caller_id_list').html(h);
      }
    }, 'json');
  });

  // select one
  $('#caller_id_widget').on('click','.caller-id-item',function(){
    let el=$(this), cid=el.data('caller_id');
    $.post(url + '?ajax=update_callerid', {
      caller_id:cid,
      use_caller_id_name:$('#use_caller_id_name').is(':checked')?1:0,
      caller_id_name:$('#caller_id_name').val()
    }, r=>{
      if (r.selected) {
        $('#caller_id_widget .caller-id-item').removeClass('selected');
        el.addClass('selected');
        $('#caller_id_widget .hud_stat').text('');
        $('#caller_id_widget .hud_badge').text(r.selected);
        $('#caller_id_widget .hud_title').text(r.name);
      }
    }, 'json');
  });
});
</script>
