<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Enhanced Draggable/Resizable)
    Version: 1.7.0 (Persistent Session)
*/

// 1) FusionPBX includes
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// 2) Permission check
if (!if_group("superadmin")
    && !permission_exists('voicemail_greeting_view')
    && !permission_exists('xml_cdr_view')
) {
    echo "access denied";
    exit;
}

$is_superadmin = if_group("superadmin");

// 3) Pull extensions
$database = new database;
if ($is_superadmin) {
    // superadmins see all enabled extensions in this domain
    $sql = "
        SELECT extension_uuid, extension, password, description
        FROM v_extensions
        WHERE domain_uuid = :domain_uuid
          AND enabled     = 'true'
    ";
    $params = ['domain_uuid'=>$_SESSION['domain_uuid']];
} else {
    // normal users only their own
    $sql = "
        SELECT e.extension_uuid, e.extension, e.password, e.description
        FROM v_extensions e
        JOIN v_extension_users eu ON e.extension_uuid = eu.extension_uuid
        WHERE e.domain_uuid = :domain_uuid
          AND eu.user_uuid   = :user_uuid
          AND e.enabled      = 'true'
    ";
    $params = [
        'domain_uuid'=>$_SESSION['domain_uuid'],
        'user_uuid'  =>$_SESSION['user_uuid']
    ];
}
$extensions = $database->select($sql, $params, 'all');

// 4) If no extensions and not superadmin → deny
if (empty($extensions) && !$is_superadmin) {
    echo "access denied";
    exit;
}

// 5) Contact name (may be empty)
$sql = "
    SELECT contact_name
    FROM view_users
    WHERE domain_name = :domain_name
      AND username    = :username
";
$params      = [
    'domain_name'=>$_SESSION['domain_name'],
    'username'   =>$_SESSION['username']
];
$contactName = $database->select($sql, $params, 'column');

// 6) Localization
$language = new text;
$text     = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');
?>
<!-- widget -->
<div class="hud_box" id="hud_phone_widget">
  <?php if (!empty($extensions) || $is_superadmin): ?>
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

<!-- load jQuery UI -->
<link href="/resources/jquery/jquery-ui.min.css" rel="stylesheet"/>
<script src="/resources/jquery/jquery-ui.min.js"></script>

<style>
  /* dashboard widget */
#hud_phone_widget {
  height: 90px;
  padding: 0 10px;
  margin-bottom: 10px;
}
.hud_content {
  display: table;
  width: 100%;
  height: 100%;
}
.hud_title,
.hud_stat {
  display: table-cell;
  vertical-align: middle;
}
.hud_stat {
  text-align: right;
}
#hud_phone_widget:hover {
  background: #f3f3f3;
}

/* phone dialog container */
#phone-dialog {
  position: fixed;
  width: 380px;
  height: 600px;
  background: #000;                 /* solid black frame */
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.7);
  z-index: 1000;
  display: none;
  border-radius: 4px;
  overflow: hidden;
  font-family: sans-serif;
}

/* header bar */
#phone-dialog .hdr {
  height: 24px;
  padding: 2px 8px;
  background: #111;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  cursor: move;
}
#phone-dialog .hdr .close {
  background: none;
  border: none;
  color: #bbb;
  font-size: 14px;
  cursor: pointer;
  line-height: 1;
}
#phone-dialog .hdr .close:hover {
  color: #fff;
}

/* toolbar (select + connect) */
#phone-dialog .toolbar {
  height: 24px;
  padding: 2px 8px;
  background: #111;
  display: flex;
  gap: 4px;
  align-items: center;
  font-size: 12px;
}
#phone-dialog .toolbar select {
  flex: 1;
  height: 20px;
  padding: 0 4px;
  font-size: 12px;
  color: #fff;
  background: #222;
  border: 1px solid #333;
  border-radius: 3px;
}
#phone-dialog .toolbar button {
  height: 20px;
  padding: 0 6px;
  font-size: 12px;
  line-height: 20px;
  background: #007acc;
  color: #fff;
  border: none;
  border-radius: 3px;
  cursor: pointer;
}
#phone-dialog .toolbar button:hover {
  background: #005fab;
}

/* content area (iframe) */
#phone-dialog .content {
  background: #000;
  height: calc(100% - (24px /* hdr */ + 24px /* toolbar */ + 24px /* footer */));
}
#phone-dialog .content iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: #000;
}

/* footer (sign out) */
#phone-dialog .footer {
  height: 24px;
  padding: 2px 8px;
  background: #111;
  text-align: right;
  font-size: 12px;
}
#phone-dialog .footer button {
  height: 20px;
  padding: 0 6px;
  font-size: 12px;
  line-height: 20px;
  background: #cc0000;
  color: #fff;
  border: none;
  border-radius: 3px;
  cursor: pointer;
}
#phone-dialog .footer button:hover {
  background: #aa0000;
}

/* ensure all resize handles are available */
#phone-dialog.ui-resizable {
  padding: 0;
}
#phone-dialog.ui-resizable .ui-resizable-handle {
  background: transparent;
}
/* end of styles */
</style>

<script>
jQuery(function($){
  // pass PHP data into JS
  var extensions        = <?= json_encode($extensions, JSON_HEX_TAG) ?>;
  var defaultContact    = <?= json_encode($contactName, JSON_HEX_TAG) ?>;
  var domain            = <?= json_encode($_SESSION['domain_name'], JSON_HEX_TAG) ?>;
  var scheme            = location.protocol;
  var currentUser       = <?= json_encode($_SESSION['username'], JSON_HEX_TAG) ?>;

  // Session storage key with user-specific prefix
  var storageKey = 'popphone_' + currentUser + '_choice';

  // build the WebRTC URL, fallback to extension if no contact name
  function buildUrl(ext, pwd){
    var name = (defaultContact && defaultContact.length) ? defaultContact : ext;
    return scheme + '//' + domain + '/Browser-Phone/Phone/index.php'
      + '?server='    + encodeURIComponent(domain)
      + '&extension=' + encodeURIComponent(ext)
      + '&password='  + encodeURIComponent(pwd)
      + '&fullname='  + encodeURIComponent(name);
  }

  // Only clear forced sign-out cookies, preserve session
  function softSignOut(src){
    // Clear only the forced logout cookies
    document.cookie.split(';').forEach(function(c){
      var n = c.split('=')[0].trim();
      if(n && (n.includes('logout') || n.includes('force'))) {
        document.cookie = n + '=;path=/Browser-Phone;expires=Thu,01 Jan 1970 00:00:00 GMT';
      }
    });
    
    // Reload the iframe
    $('#phone-iframe').attr('src', src || 'about:blank');
  }

  // Full sign out including clearing storage
  function hardSignOut(src){
    // Clear all Browser-Phone cookies
    document.cookie.split(';').forEach(function(c){
      var n = c.split('=')[0].trim();
      if(n) {
        document.cookie = n + '=;path=/Browser-Phone;expires=Thu,01 Jan 1970 00:00:00 GMT';
      }
    });
    
    // Clear storage
    try {
      var w = $('#phone-iframe')[0].contentWindow;
      w.localStorage.clear(); 
      w.sessionStorage.clear();
    } catch(e) {}
    
    // Remove saved choice
    localStorage.removeItem(storageKey);
    
    // Reload
    $('#phone-iframe').attr('src', src || 'about:blank');
  }

  // build + show the dialog
  function openDialog(){
    if(!$('#phone-dialog').length){
      // toolbar only if >1 extension
      var toolbar = extensions.length>1
        ? '<div class="toolbar">'
        +    '<select id="ext-select">'
        +      extensions.map(function(e){
               return '<option value="'+e.extension+'|'+e.password+'">'
                    +   e.extension
                    +   (e.description?(' – '+e.description):'')
                    + '</option>';
             }).join('')
        +    '</select>'
        +    '<button id="btn-connect">Connect</button>'
        +  '</div>'
        : '';
      
      $('body').append(
        '<div id="phone-dialog">'
      +   '<div class="hdr">'
      +     '<span>Browser Phone</span>'
      +     '<button class="close" onclick="$(\'#phone-dialog\').hide()">×</button>'
      +   '</div>'
      +   toolbar
      +   '<div class="content"><iframe id="phone-iframe" allow="camera; microphone" scrolling="no"></iframe></div>'
      +   '<div class="footer"><button id="btn-signout">Sign Out</button></div>'
      +'</div>'
      );
      
      // draggable + resizable
        $('#phone-dialog').draggable({
        handle: '.hdr',
        containment: 'window',
        iframeFix: true
        }).resizable({
        // enable resizing from all edges & corners
        handles: 'n, e, s, w, ne, se, sw, nw',
        minWidth: 300,
        minHeight: 400,
        ghost: true,              // draw a lightweight outline during the drag
        animate: true,            // animate to the final size on stop
        animateDuration: 100,     // 100ms snap animation
        animateEasing: 'swing',   // easing style
        start: function() {
            // disable pointer-events on iframe for smooth dragging
            $('#phone-iframe').css('pointer-events', 'none');
        },
        stop: function(event, ui) {
            // re-enable pointer-events and resize iframe to fit
            $('#phone-iframe')
            .css('pointer-events', 'auto')
            .height(
                ui.size.height
                - $('.hdr').outerHeight()
                - $('.toolbar').outerHeight()
                - $('.footer').outerHeight()
            );
        }
        });
      
      // Connect button
      $('#btn-connect').click(function(){
        var parts = $('#ext-select').val().split('|');
        // remember choice for auto-login
        localStorage.setItem(storageKey, parts[0]+'|'+parts[1]);
        softSignOut(buildUrl(parts[0],parts[1]));
      });
      
      // auto-connect if only one extension
      if(extensions.length===1){
        var e=extensions[0];
        localStorage.setItem(storageKey, e.extension+'|'+e.password);
        softSignOut(buildUrl(e.extension,e.password));
      }
      
      // remember last for sign-out
      $('#phone-iframe').on('load',function(){
        $(this).data('last',this.src);
      });
      
      // Sign Out button (full sign out)
      $('#btn-signout').click(function(){
        var last = $('#phone-iframe').data('last') || 'about:blank';
        hardSignOut(last);
      });
    }

    // auto-login if we have stored choice
    var saved = localStorage.getItem(storageKey);
    if(saved && extensions.length>1){
      var p = saved.split('|');
      softSignOut(buildUrl(p[0],p[1]));
    } else if ($('#phone-iframe').attr('src')) {
      // Just refresh if already has a src
      softSignOut($('#phone-iframe').attr('src'));
    }

    // center & show
    var D = $('#phone-dialog');
    D.css({
      left:($(window).width()-D.outerWidth())/2+'px',
      top: ($(window).height()-D.outerHeight())/2+'px'
    }).show();
  }

  $('#launch_phone_button').click(openDialog);
  
  // Persist the iframe when navigating away
  var phoneIframe = null;
  $(window).on('beforeunload', function() {
    phoneIframe = $('#phone-iframe').detach();
  });
  
  $(window).on('load', function() {
    if (phoneIframe) {
      $('.content').append(phoneIframe);
      phoneIframe = null;
    }
  });
});
</script>