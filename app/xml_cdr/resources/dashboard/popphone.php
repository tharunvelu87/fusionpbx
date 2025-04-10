<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Non-modal version)
    Version: MPL 1.1
*/

// Correctly include FusionPBX central resources by going four levels up.
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// Check permissions.
if (!(permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view'))) {
    echo "access denied";
    exit;
}

// Add multi-lingual support.
$language = new text;
$text = $language->get();

// Retrieve current user's extension details.
$sql = "SELECT extension_uuid FROM v_extension_users 
        WHERE domain_uuid = :domain_uuid 
          AND user_uuid = :user_uuid";
$params = [
    'domain_uuid' => $_SESSION['domain_uuid'],
    'user_uuid'   => $_SESSION['user_uuid']
];
$database = new database;
$extension_uuid = $database->select($sql, $params, 'column');

$sql = "SELECT extension, password FROM v_extensions 
        WHERE extension_uuid = :extension_uuid";
$params = ['extension_uuid' => $extension_uuid];
$row = $database->select($sql, $params, 'row');
$extension = $row['extension'] ?? null;
$password  = $row['password'] ?? null;

$sql = "SELECT contact_name FROM view_users 
        WHERE domain_name = :domain_name 
          AND username = :username";
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
    Widget Markup: The entire box is clickable and styled using hud_box classes.
-->
<div class="hud_box" id="hud_phone_widget" style="cursor: pointer;" onclick="OpenPhoneWindow();">
    <div class="hud_content" style="text-align: center;">
        <div style="margin-bottom: 10px;">
            <i class="fas fa-phone" style="font-size: 40px;"></i>
        </div>
        <div class="hud_title">Launch Phone</div>
    </div>
</div>

<!-- Slight hover highlight -->
<style>
#hud_phone_widget:hover {
    background-color: #f3f3f3;
}
#hud_phone_widget {
    min-height: 120px; /* Adjust as needed to match similar widgets */
}
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

<!-- JavaScript function to open the phone in a non-modal window -->
<script type="text/javascript">
function OpenPhoneWindow() {
    var phoneUrl = "<?php echo $phone_url; ?>";
    var width  = 400;
    var height = 700;
    // Create an iframe with the Browser Phone interface.
    var $iframe = $("<iframe>", {
        src: phoneUrl,
        css: { width: "100%", height: "100%" },
        attr: {
            frameborder: "0",
            allow: "camera; microphone",
            scrolling: "no"  // Disable internal scrollbars.
        }
    });
    // Create the jQuery UI dialog as non-modal (modeless).
    var $window = $("<div>").html($iframe).dialog({
        title: "Browser Phone",
        modal: false,       // Not modal: background remains accessible.
        width: width,
        height: height,
        resizable: true,
        draggable: true,
        close: function(){
            $(this).dialog("destroy").remove();
        }
    });
    // Center the dialog.
    var windowWidth  = $(window).width();
    var windowHeight = $(window).height();
    $window.parent().css({
        left: (windowWidth - width) / 2 + "px",
        top: (windowHeight - height) / 2 + "px",
        position: "fixed",
        zIndex: 9999
    });
}
</script>
