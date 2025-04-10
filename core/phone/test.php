<?php
// dashboard_phone_widget.php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

// (Optional) Verify permission for using the phone widget.
if (!permission_exists('voicemail_greeting_view') && !permission_exists('xml_cdr_view')) {
    echo "access denied";
    exit;
}

// Set up multi-lingual support.
$language = new text;
$text = $language->get();

// Retrieve user extension details.
$sql = "SELECT extension_uuid FROM v_extension_users 
        WHERE domain_uuid = '" . $domain_uuid . "' 
          AND user_uuid = '".$_SESSION['user_uuid']."'";
$database = new database;
$extension_uuid = $database->select($sql, null, 'column');
unset($sql);

$sql = "SELECT extension, password FROM v_extensions 
        WHERE extension_uuid = '".$extension_uuid."'";
$database = new database;
$row = $database->select($sql, null, 'all');
$extension = $row[0]['extension'];
$password = $row[0]['password'];
unset($sql);

$sql = "SELECT contact_name FROM view_users 
        WHERE domain_name = '".$_SESSION['domain_name']."' 
          AND username = '".$_SESSION['username']."'";
$database = new database;
$contactName = $database->select($sql, null, 'column');
if ($contactName == "") {
    $contactName = $extension;
}
unset($sql);

// Build the dynamic phone URL.
$phone_url  = "https://" . $_SESSION['domain_name'] . "/Browser-Phone/Phone/index.php";
$phone_url .= "?server=" . urlencode($_SESSION['domain_name']);
$phone_url .= "&extension=" . urlencode($extension);
$phone_url .= "&password=" . urlencode($password);
$phone_url .= "&fullname=" . urlencode($contactName);
?>

<!-- Dashboard Widget Styles and Scripts -->
<link rel="stylesheet" href="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.css"/>
<!-- Include jQuery and jQuery UI (if not already loaded) -->
<script src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-3.3.1.min.js"></script>
<script src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.js"></script>

<style>
    /* Widget container styling */
    #dashboard-phone-widget {
        text-align: center;
        margin: 10px;
    }
    /* Button styling */
    #dashboard-phone-button {
        font-size: 16px;
        background-color: #0073AA;
        border: none;
        color: #fff;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
    }
    #dashboard-phone-button:hover {
        background-color: #005F8D;
    }
</style>

<div id="dashboard-phone-widget">
    <!-- Button to trigger the Phone overlay popup -->
    <button id="dashboard-phone-button">Open Phone</button>
</div>

<script>
$(document).ready(function(){
    $("#dashboard-phone-button").click(function(e){
        e.preventDefault();

        // Build the phone URL that was generated server-side.
        var phoneUrl = "<?php echo $phone_url; ?>";

        // Create an iframe that loads the Phone interface.
        var $iframe = $("<iframe>", {
            src: phoneUrl,
            css: { width: "100%", height: "100%" },
            attr: { frameborder: "0" }
        });

        // Create the modal dialog using jQuery UI.
        var $modal = $("<div>").html($iframe).dialog({
            title: "Browser Phone",
            modal: true,
            width: 400,
            height: 700,
            resizable: true,
            draggable: true,
            close: function(){
                $(this).dialog("destroy").remove();
            }
        });

        // Optional: Center the dialog manually if desired.
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();
        $modal.parent().css({
            left: (windowWidth - 400) / 2 + "px",
            top: (windowHeight - 700) / 2 + "px",
            position: "fixed",
            zIndex: 9999
        });
    });
});
</script>
