<?php
/*
    popphone.php
    FusionPBX Phone Dashboard Widget (Inline Version)
    Version: MPL 1.1

    This widget loads the Browser Phone interface directly inside the widget container.
    The iframe is sized to fill the available widget space (which you can adjust via widget settings).
*/

// Include FusionPBX central resources.
// Adjust the number (4) below if your file location changes.
require_once dirname(__DIR__, 4) . '/resources/require.php';
require_once dirname(__DIR__, 4) . '/resources/check_auth.php';

// Check permissions: Only allow users with permission to view the phone.
if (!(permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view'))) {
    echo "access denied";
    exit;
}

// Add multi-lingual support.
$language = new text;
$text = $language->get();

// Retrieve the current user's extension details.
$sql = "SELECT extension_uuid FROM v_extension_users 
        WHERE domain_uuid = :domain_uuid 
          AND user_uuid = :user_uuid";
$params = [
    'domain_uuid' => $_SESSION['domain_uuid'],
    'user_uuid'   => $_SESSION['user_uuid']
];
$database = new database;
$extension_uuid = $database->select($sql, $params, 'column');

// Retrieve extension and password.
$sql = "SELECT extension, password FROM v_extensions 
        WHERE extension_uuid = :extension_uuid";
$params = ['extension_uuid' => $extension_uuid];
$row = $database->select($sql, $params, 'row');
$extension = $row['extension'] ?? '';
$password  = $row['password']  ?? '';

// Retrieve contact_name; if empty, default to extension.
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

// Build the dynamic URL for the Browser Phone interface.
$phone_url  = "https://" . $_SESSION['domain_name'] . "/Browser-Phone/Phone/index.php";
$phone_url .= "?server="    . urlencode($_SESSION['domain_name']);
$phone_url .= "&extension=" . urlencode($extension);
$phone_url .= "&password="  . urlencode($password);
$phone_url .= "&fullname="  . urlencode($contactName);
?>

<!--
    Widget Markup:
    This widget uses the same FusionPBX HUD box structure. The inline <iframe>
    loads the Browser Phone interface. The iframe is set to fill the widget's entire area.
-->
<div class="hud_box" id="hud_phone_widget" style="overflow: hidden; width: 100%; height: 100%;">
    <iframe src="<?php echo $phone_url; ?>" 
        style="width: 100%; height: 100%; border: none;" 
        scrolling="no">
    </iframe>
</div>
