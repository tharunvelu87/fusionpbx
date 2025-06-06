<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Non-modal version)
    Version: MPL 1.1
*/

// Include FusionPBX resources.
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// Check permissions.
if (!(permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view'))) {
    echo "access denied";
    exit;
}

// Multi-lingual support.
$language = new text;
$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

// Retrieve current user's extension details.
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
$database = new database;
$extension_uuid = $database->select($sql, $params, 'column');

$sql = "
    SELECT extension, password
    FROM v_extensions
    WHERE extension_uuid = :extension_uuid
";
$params = ['extension_uuid' => $extension_uuid];
$row = $database->select($sql, $params, 'row');
$extension = $row['extension'] ?? null;
$password  = $row['password'] ?? null;

// Retrieve contact name (real name) for display.
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
    $contactName = $extension;
}

// Build the dynamic Browser Phone URL.
$phone_url  = "https://" . $_SESSION['domain_name'] . "/Browser-Phone/Phone/index.php";
$phone_url .= "?server="    . urlencode($_SESSION['domain_name']);
$phone_url .= "&extension=" . urlencode($extension);
$phone_url .= "&password="  . urlencode($password);
$phone_url .= "&fullname="  . urlencode($contactName);
?>

<!-- 
    PopPhone Widget Markup:
    Uses a fixed height (90px) with a table display on .hud_content to vertically center
    the title and icon in the same manner as other FusionPBX dashboard widgets.
-->
<div class="hud_box" id="hud_phone_widget">
    <div class="hud_content" onclick="OpenPhoneWindow();" style="cursor: pointer;">
        <span class="hud_title">
            <?php echo $text['label-launch_phone'] ?? 'Launch Phone'; ?>
        </span>
        <span class="hud_stat">
            <!-- Using purple (#8e44ad). Change to green (#27ae60) or orange (#e67e22) if desired -->
            <i class="fas fa-phone" style="font-size: 32px; color: #8e44ad;"></i>
        </span>
    </div>
</div>

<!-- CSS for aligning with other dashboard widgets -->
<style>
    /* The widget container uses a fixed height of 90px to match others */
    #hud_phone_widget {
        height: 90px;
        padding: 0 10px;
        margin-bottom: 10px;
    }
    /* Use a table display on the content container for vertical centering */
    .hud_content {
        display: table;
        width: 100%;
        height: 100%;
    }
    /* Table-cell display for title and stat allows vertical centering */
    .hud_title,
    .hud_stat {
        display: table-cell;
        vertical-align: middle;
    }
    .hud_stat {
        text-align: right;
    }
    /* Hover effect to match theme */
    #hud_phone_widget:hover {
        background-color: #f3f3f3;
    }
    /* Ensure any dialogs do not override the widget styling */
    .ui-dialog .ui-dialog-content {
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }
</style>

<!-- Include jQuery & jQuery UI if not already loaded -->
<script src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-3.3.1.min.js"></script>
<script src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.css"/>

<!-- JavaScript function to open the Browser Phone interface in a non-modal dialog -->
<script type="text/javascript">
function OpenPhoneWindow() {
    var phoneUrl = "<?php echo $phone_url; ?>";
    var width  = 400;
    var height = 700;

    // Create an iframe for the Browser Phone interface.
    var $iframe = $("<iframe>", {
        src: phoneUrl,
        css: { width: "100%", height: "100%" },
        attr: {
            frameborder: "0",
            allow: "camera; microphone",
            scrolling: "no"
        }
    });

    // Create the jQuery UI dialog (modeless).
    var $window = $("<div>").html($iframe).dialog({
        title: "<?php echo $text['label-browser_phone'] ?? 'Browser Phone'; ?>",
        modal: false,
        width: width,
        height: height,
        resizable: true,
        draggable: true,
        close: function(){
            $(this).dialog("destroy").remove();
        }
    });

    // Center the dialog on screen.
    var windowWidth  = $(window).width();
    var windowHeight = $(window).height();
    $window.parent().css({
        left: (windowWidth - width) / 2 + "px",
        top:  (windowHeight - height) / 2 + "px",
        position: "fixed",
        zIndex: 9999
    });
}
</script>
