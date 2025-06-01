<?php
/**
 * Standalone Browser Phone Interface
 *
 * This file is a combined version that loads FusionPBX’s header and footer.
 * It auto-loads the current user’s phone credentials (extension, password,
 * full name) from the database and embeds the Browser-Phone interface in an iframe.
 */

require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

// Redirect admin to app if necessary.
if (file_exists($_SERVER["PROJECT_ROOT"] . "/app/domains/app_config.php") && !permission_exists('domain_all')) {
    header("Location: " . PROJECT_PATH . "/app/domains/domains.php");
    exit;
}

// Check that the user has at least one of the needed permissions.
if (!(permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view'))) {
    echo "access denied";
    exit;
}

// Add multi-lingual support.
$language = new text;
$text = $language->get();

// (Optional) Remove or adapt domain change handling if not needed in standalone mode.

// Set the page title and load FusionPBX header.
$document['title'] = "Phone";
require_once "resources/header.php";

// Retrieve extension credentials for the logged-in user.
$domain_uuid = $_SESSION['domain_uuid'];
$user_uuid = $_SESSION['user_uuid'];
$database = new database;

// Get the extension_uuid for the current user.
$sql = "SELECT extension_uuid FROM v_extension_users WHERE domain_uuid = :domain_uuid AND user_uuid = :user_uuid";
$parameters = [
    'domain_uuid' => $domain_uuid,
    'user_uuid'   => $user_uuid,
];
$extension_uuid = $database->select($sql, $parameters, 'column');
unset($sql, $parameters);

// Get the extension number and password from v_extensions.
$sql = "SELECT extension, password FROM v_extensions WHERE extension_uuid = :extension_uuid";
$parameters = ['extension_uuid' => $extension_uuid];
$rows = $database->select($sql, $parameters, 'all');
if (is_array($rows) && count($rows) > 0) {
    $extension = $rows[0]['extension'];
    $password  = $rows[0]['password'];
} else {
    $extension = "";
    $password  = "";
}
unset($sql, $parameters);

// Get the contact (full) name from view_users.
$sql = "SELECT contact_name FROM view_users WHERE domain_name = :domain_name AND username = :username";
$parameters = [
    'domain_name' => $_SESSION['domain_name'],
    'username'    => $_SESSION['username']
];
$contactName = $database->select($sql, $parameters, 'column');
if ($contactName == "" || is_null($contactName)) {
    $contactName = $extension;
}
unset($sql, $parameters);
?>
<!-- Display the Browser Phone interface using an iframe -->
<div style="height: 77vh;">
    <iframe src="https://<?php echo $_SESSION['domain_name']; ?>/Browser-Phone/Phone/index.php?server=<?php echo $_SESSION['domain_name']; ?>&extension=<?php echo $extension; ?>&password=<?php echo $password; ?>&fullname=<?php echo urlencode($contactName); ?>" 
            width="100%" 
            height="100%" 
            frameborder="0"></iframe>
</div>

<?php
// Include FusionPBX footer.
require_once "resources/footer.php";
?>
