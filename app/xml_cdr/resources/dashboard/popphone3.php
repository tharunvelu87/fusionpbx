<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Enhanced Draggable/Resizable)
    Version: 1.6.1 (Syntax-corrected)
*/

// 1) Include FusionPBX core
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// 2) Permission check (superadmin OR voicemail_greeting_view OR xml_cdr_view)
if (!if_group("superadmin")
    && !permission_exists('voicemail_greeting_view')
    && !permission_exists('xml_cdr_view')
) {
    echo "access denied";
    exit;
}

// 3) Multi-lingual support
$language = new text;
$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

// 4) Fetch all extensions for the current user in this domain
$sql = "
    SELECT e.extension_uuid, e.extension, e.password, e.description
    FROM v_extensions e
    JOIN v_extension_users eu ON e.extension_uuid = eu.extension_uuid
    WHERE e.domain_uuid = :domain_uuid
    AND eu.user_uuid = :user_uuid
    AND e.enabled = 'true'
";
$params = [
    'domain_uuid' => $_SESSION['domain_uuid'],
    'user_uuid'   => $_SESSION['user_uuid']
];
$database = new database;
$extensions = $database->select($sql, $params, 'all');

// 5) If there are no extensions for this user in this domain, deny unless superadmin
if (empty($extensions)) {
    if (!if_group("superadmin")) {
        echo "access denied";
        exit;
    }
    // For superadmin with no extensions, create an empty array
    $extensions = [];
}

// 6) Fetch contact_name (fallback to username or "Unknown")
$sql = "
    SELECT contact_name
    FROM view_users
    WHERE domain_name = :domain_name
    AND username = :username
";
$params = [
    'domain_name' => $_SESSION['domain_name'],
    'username'    => $_SESSION['username']
];
$contactName = $database->select($sql, $params, 'column');
if (empty($contactName)) {
    $contactName = $_SESSION['username'] ?? "Unknown";
}

// 7) Build Browser-Phone URL function
function build_phone_url($extension, $password, $contactName) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
              ? 'https'
              : 'http';
    $phone_url  = $protocol . '://' . $_SESSION['domain_name'] . '/Browser-Phone/Phone/index.php';
    $phone_url .= '?server='    . urlencode($_SESSION['domain_name']);
    $phone_url .= '&extension=' . urlencode($extension);
    $phone_url .= '&password='  . urlencode($password);
    $phone_url .= '&fullname='  . urlencode($contactName);
    return $phone_url;
}
?>
<!-- 8) Dashboard widget box -->
<div class="hud_box" id="hud_phone_widget">
  <?php if (!empty($extensions) || if_group("superadmin")): ?>
    <div class="hud_content" id="launch_phone_button" style="cursor:pointer">
      <span class="hud_title"><?= $text['label-launch_phone'] ?? 'Launch Phone' ?></span>
      <span class="hud_stat"><i class="fas fa-phone" style="font-size:32px;color:#8e44ad"></i></span>
    </div>
  <?php else: ?>
    <div class="hud_content" style="color:#e74c3c">
      <span class="hud_title"><?= $text['label-no_extension'] ?? 'No extension assigned' ?></span>
      <span class="hud_stat"><i class="fas fa-exclamation-triangle" style="font-size:32px"></i></span>
    </div>
  <?php endif; ?>
</div>

<!-- 9) jQuery UI + compact dialog styles -->
<link href="/resources/jquery/jquery-ui.min.css" rel="stylesheet"/>
<script src="/resources/jquery/jquery-ui.min.js"></script>

<style>
/* … keep your hud_* styles … */

/* Compact dialog */
#phone-dialog {
  position:fixed; width:380px; height:600px;
  background:#fff; border:1px solid #ddd;
  box-shadow:0 2px 8px rgba(0,0,0,0.1);
  z-index:1000; display:none; border-radius:4px;
  font-family:sans-serif;
}
#phone-dialog .hdr {
  padding:6px 10px; background:#fafafa;
  border-bottom:1px solid #eee; cursor:move;
  font-size:13px; display:flex; justify-content:space-between;
}
#phone-dialog .hdr .close {
  border:none; background:none; font-size:16px;
  cursor:pointer; color:#888;
}
#phone-dialog .toolbar {
  padding:4px 10px; background:#f7f7f7;
  border-bottom:1px solid #eee; font-size:13px;
  display:flex; align-items:center; gap:6px;
}
#phone-dialog .toolbar select {
  flex:1; padding:4px; font-size:13px;
  border:1px solid #ccc; border-radius:3px;
}
#phone-dialog .toolbar button {
  padding:4px 8px; font-size:13px;
  background:#4caf50; color:#fff; border:none;
  border-radius:3px; cursor:pointer;
}
#phone-dialog .toolbar button:hover { background:#43a047 }
#phone-dialog .content {
  height: calc(100% - 66px); /* hdr + toolbar + signout bar */
}
#phone-dialog iframe { width:100%; height:100%; border:none }
#phone-dialog .footer {
  padding:4px 10px; background:#fafafa;
  border-top:1px solid #eee; text-align:right;
}
#phone-dialog .footer button {
  padding:3px 6px; font-size:12px;
  background:#e74c3c; color:#fff; border:none;
  border-radius:3px; cursor:pointer;
}
#phone-dialog .footer button:hover { background:#c62828 }
</style>

<script>
jQuery(function($){
  var extensions = <?= json_encode($extensions) ?>;
  var contactName = <?= json_encode($contactName) ?>;
  var domain = <?= json_encode($_SESSION['domain_name']) ?>;
  var scheme = location.protocol;

  function buildUrl(ext,pwd){
    return scheme+'//'+domain+'/Browser-Phone/Phone/index.php'
      + '?server='+encodeURIComponent(domain)
      + '&extension='+encodeURIComponent(ext)
      + '&password='+encodeURIComponent(pwd)
      + '&fullname='+encodeURIComponent(contactName);
  }

  function signOutThen(src){
    // 1) clear cookies under /Browser-Phone
    document.cookie.split(';').forEach(function(c){
      var name=c.split('=')[0].trim();
      if(name) document.cookie = name+'=;path=/Browser-Phone;expires=Thu,01 Jan 1970 00:00:00 GMT';
    });
    // 2) clear storage if same-origin
    try{
      var win=$('#phone-iframe')[0].contentWindow;
      win.localStorage.clear(); win.sessionStorage.clear();
    }catch(e){}
    // 3) reload
    $('#phone-iframe').attr('src','about:blank');
    setTimeout(function(){ $('#phone-iframe').attr('src',src) }, 100);
  }

  function openDialog(){
    if($('#phone-dialog').length===0){
      // build once
      $('body').append(`
        <div id="phone-dialog">
          <div class="hdr">
            <span>Browser Phone</span>
            <button class="close" onclick="$('#phone-dialog').hide()">×</button>
          </div>
          ${ extensions.length>1
            ? `<div class="toolbar">
                 <select id="ext-select">
                   ${ extensions.map(e=>
                       `<option value="${e.extension}|${e.password}">
                         ${e.extension}${e.description?(' – '+e.description):''}
                       </option>`
                    ).join('') }
                 </select>
                 <button id="btn-connect">Connect</button>
               </div>`
            : ''
          }
          <div class="content">
            <iframe id="phone-iframe" src="" allow="camera; microphone" scrolling="no"></iframe>
          </div>
          <div class="footer">
            <button id="btn-signout">Sign Out</button>
          </div>
        </div>`);
      // draggable + resizable
      $('#phone-dialog').draggable({
        handle:'.hdr', containment:'window', iframeFix:true
      }).resizable({
        handles:'se', minWidth:300, minHeight:400
      });

      // wire up toolbar
      $('#btn-connect').click(function(){
        var parts = $('#ext-select').val().split('|');
        signOutThen( buildUrl(parts[0],parts[1]) );
      });
      if(extensions.length===1){
        var e = extensions[0];
        $('#btn-connect').length
          ? $('#btn-connect').click()
          : signOutThen( buildUrl(e.extension,e.password) );
      }

      // signout bar
      $('#btn-signout').click(function(){
        var cur = $('#phone-iframe').data('last')||'about:blank';
        signOutThen(cur);
      });
      
      // remember last URL
      $('#phone-iframe').on('load',function(){
        $(this).data('last',this.src);
      });
    } 

    // center & show
    var D = $('#phone-dialog');
    D.css({
      left:($(window).width()-D.outerWidth())/2+'px',
      top: ($(window).height()-D.outerHeight())/2+'px'
    }).show();
  }

  $('#launch_phone_button').click(openDialog);
});
</script>