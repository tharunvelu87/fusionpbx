<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Enhanced Draggable/Resizable)
    Version: 1.5.2 (Superadmin‐compatible)

    • Includes FusionPBX core
    • Checks permissions (superadmin OR voicemail_greeting_view OR xml_cdr_view)
    • Loads the current user’s extension + password (if they have one in this domain)
    • Builds a “Launch Phone” icon that, when clicked, spawns a jQuery UI dialog containing the Browser-Phone iframe
    • Always loads jQuery UI (draggable/resizable) from /resources/jquery
*/

// 1) Include FusionPBX core
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// 2) Permission check (superadmin OR voicemail_greeting_view OR xml_cdr_view)
if (
    ! if_group("superadmin")
    && ! permission_exists('voicemail_greeting_view')
    && ! permission_exists('xml_cdr_view')
) {
    echo "access denied";
    exit;
}

// 3) Multi-lingual support
$language = new text;
$text     = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

// 4) Fetch the user’s extension_uuid (for the current domain)
$sql = "
    SELECT extension_uuid
      FROM v_extension_users
     WHERE domain_uuid = :domain_uuid
       AND user_uuid   = :user_uuid
";
$params = [
    'domain_uuid' => $_SESSION['domain_uuid'],
    'user_uuid'   => $_SESSION['user_uuid']
];
$database       = new database;
$extension_uuid = $database->select($sql, $params, 'column');

// 5) Fetch extension + password (if that extension_uuid exists)
$extension = null;
$password  = null;
if (!empty($extension_uuid)) {
    $sql = "
        SELECT extension, password
          FROM v_extensions
         WHERE extension_uuid = :extension_uuid
           AND domain_uuid    = :domain_uuid
    ";
    $params = [
        'extension_uuid' => $extension_uuid,
        'domain_uuid'    => $_SESSION['domain_uuid']
    ];
    $row = $database->select($sql, $params, 'row');
    if (!empty($row)) {
        $extension = $row['extension'];
        $password  = $row['password'];
    }
}

// 6) If there’s no extension/password for this user in this domain, deny unless superadmin
if (
    (empty($extension) || empty($password))
    && ! if_group("superadmin")
) {
    echo "access denied";
    exit;
}

// 7) Fetch contact_name (fallback to extension or “Unknown”)
$sql = "
    SELECT contact_name
      FROM view_users
     WHERE domain_name = :domain_name
       AND username    = :username
";
$params = [
    'domain_name' => $_SESSION['domain_name'],
    'username'    => $_SESSION['username']
];
$contactName = $database->select($sql, $params, 'column');
if (empty($contactName)) {
    if (!empty($extension)) {
        $contactName = $extension;
    }
    else {
        // superadmin with no extension: use “Unknown”
        $contactName = "Unknown";
    }
}

// 8) Build Browser-Phone URL (only if extension/password exist)
$phone_url = "";
if (!empty($extension) && !empty($password)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
              ? 'https'
              : 'http';
    $phone_url  = $protocol . '://' . $_SESSION['domain_name'] . '/Browser-Phone/Phone/index.php';
    $phone_url .= '?server='    . urlencode($_SESSION['domain_name']);
    $phone_url .= '&extension=' . urlencode($extension);
    $phone_url .= '&password='  . urlencode($password);
    $phone_url .= '&fullname='  . urlencode($contactName);
}
?>

<!-- 9) Dashboard widget box (clicking only works if an extension was found) -->
<div class="hud_box" id="hud_phone_widget">
  <?php if (!empty($phone_url)): ?>
    <!-- user has an extension → clickable -->
    <div
      class="hud_content"
      id="launch_phone_button"
      style="cursor: pointer;"
      onclick="openPhoneDialog();"
    >
      <span class="hud_title">
        <?php echo $text['label-launch_phone'] ?? 'Launch Phone'; ?>
      </span>
      <span class="hud_stat">
        <i class="fas fa-phone" style="font-size: 32px; color: #8e44ad;"></i>
      </span>
    </div>
  <?php else: ?>
    <!-- no extension → show error -->
    <div class="hud_content" style="color: #e74c3c; cursor: default;">
      <span class="hud_title">
        <?php echo $text['label-no_extension'] ?? 'No extension assigned'; ?>
      </span>
      <span class="hud_stat">
        <i class="fas fa-exclamation-triangle" style="font-size: 32px;"></i>
      </span>
    </div>
  <?php endif; ?>
</div>

<!-- 10) CSS to match FusionPBX dashboard style -->
<style>
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
    .hud_title, .hud_stat {
        display: table-cell;
        vertical-align: middle;
    }
    .hud_stat {
        text-align: right;
    }
    #hud_phone_widget:hover {
        background-color: #f3f3f3;
    }

    /* Dialog container */
    #phone-dialog {
        position: fixed;
        width: 400px;
        height: 600px;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        border-radius: 3px;
    }
    #phone-dialog .phone-dialog-header {
        padding: 5px 8px;
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        cursor: move;
        font-size: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #phone-dialog .phone-dialog-title {
        font-weight: bold;
        color: #555;
    }
    #phone-dialog .phone-dialog-close {
        background: none;
        border: none;
        font-size: 16px;
        cursor: pointer;
        padding: 0;
        color: #777;
        line-height: 1;
    }
    #phone-dialog .phone-dialog-close:hover {
        color: #333;
    }
    #phone-dialog .phone-dialog-content {
        width: 100%;
        height: calc(100% - 30px);
        padding: 0;
        margin: 0;
        overflow: hidden;
    }
</style>

<!-- 11) Load jQuery UI & autosizeInput unconditionally -->
<link href="/resources/jquery/jquery-ui.min.css" rel="stylesheet"/>
<script src="/resources/jquery/jquery-ui.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Only bind click if phone_url exists
    <?php if (!empty($phone_url)): ?>
    $('#launch_phone_button').on('click', function() {
        // 11.a) Build dialog HTML on first click
        if ($('#phone-dialog').length === 0) {
            var dialogHtml = '\
                <div id="phone-dialog">\
                    <div class="phone-dialog-header">\
                        <span class="phone-dialog-title"><?php echo $text['label-browser_phone'] ?? 'Browser Phone'; ?></span>\
                        <button class="phone-dialog-close" onclick="closePhoneDialog()">×</button>\
                    </div>\
                    <div class="phone-dialog-content">\
                        <iframe id="phone-dialog-iframe" src="" style="width:100%; height:100%; border:none;"\
                                allow="camera; microphone" scrolling="no"></iframe>\
                    </div>\
                </div>';
            $('body').append(dialogHtml);

            // 11.b) Make it draggable
            $('#phone-dialog').draggable({
                handle: '.phone-dialog-header',
                containment: 'window',
                scroll: false,
                iframeFix: true,
                start: function() {
                    $('#phone-dialog-iframe').css('pointer-events', 'none');
                },
                stop: function() {
                    $('#phone-dialog-iframe').css('pointer-events', 'auto');
                }
            });

            // 11.c) Make it resizable at the southeast corner
            $('#phone-dialog').resizable({
                handles: "se",
                minWidth: 300,
                minHeight: 400,
                start: function() {
                    $('#phone-dialog-iframe').css('pointer-events', 'none');
                },
                stop: function() {
                    $('#phone-dialog-iframe').css('pointer-events', 'auto');
                },
                resize: function() {
                    $('#phone-dialog-iframe').css({
                        width:  $('#phone-dialog').width() + 'px',
                        height: ($('#phone-dialog').height() - $('.phone-dialog-header').outerHeight()) + 'px'
                    });
                }
            });
        }

        // 11.d) Center the dialog each time
        var dlg = $('#phone-dialog');
        dlg.css({
            left: ($(window).width() - dlg.outerWidth()) / 2 + 'px',
            top:  ($(window).height() - dlg.outerHeight()) / 2 + 'px'
        });

        // 11.e) Load iframe src & show
        $('#phone-dialog-iframe').attr('src', '<?php echo $phone_url; ?>');
        dlg.show();
    });
    <?php endif; ?>
});

// 12) Close dialog
function closePhoneDialog() {
    jQuery('#phone-dialog').hide();
    jQuery('#phone-dialog-iframe').attr('src', '');
}

// 13) Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && jQuery('#phone-dialog').is(':visible')) {
        closePhoneDialog();
    }
});
</script>