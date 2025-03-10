<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/paging.php";

// Redirect admin to app instead
if (file_exists($_SERVER["PROJECT_ROOT"] . "/app/domains/app_config.php") && !permission_exists('domain_all')) {
    header("Location: " . PROJECT_PATH . "/app/domains/domains.php");
    exit;
}

// Check permissions
if (permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view')) {
    // Access granted
} else {
    echo "access denied";
    exit;
}

// Add multi-lingual support
$language = new text;
$text = $language->get();

// Change the domain
if (is_uuid($_GET["domain_uuid"]) && $_GET["domain_change"] == "true") {
    if (permission_exists('domain_select')) {
        // Get the domains list
        $sql = "SELECT * FROM v_domains";
        $database = new database;
        $result = $database->select($sql, null, 'all');

        if (is_array($result) && count($result) > 0) {
            foreach ($result as $row) {
                if ($row['domain_name'] == $_SERVER['HTTP_HOST'] || $row['domain_name'] == 'www.' . $_SERVER['HTTP_HOST']) {
                    $_SESSION["domain_uuid"] = $row["domain_uuid"];
                    $_SESSION["domain_name"] = $row['domain_name'];
                    break;
                }
            }
        }

        // Update session variables for selected domain
        $domain_uuid = $_GET["domain_uuid"];
        $_SESSION['domain_uuid'] = $domain_uuid;
        $_SESSION["domain_name"] = $_SESSION['domains'][$domain_uuid]['domain_name'] ?? '';
        $_SESSION['domain']['template']['name'] = $_SESSION['domains'][$domain_uuid]['template_name'] ?? '';

        // Clear the extension array so it is regenerated for the selected domain
        unset($_SESSION['extension_array']);

        // Set the domain
        $domain = new domains();
        $domain->db = $db;
        $domain->set();

        // Redirect the user to the appropriate page
        $destination_url = $_SESSION["login"]["destination"]["url"] ?? "/core/user_settings/user_dashboard.php";
        header("Location: " . PROJECT_PATH . $destination_url);
        exit;
    }
}

// Redirect the user to the domains page if it exists
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/app/domains/domains.php")) {
    $href = '/app/domains/domains.php';
}

// Include additional files
echo "<style> .card{height:77vh;} </style>";
require_once "resources/header.php";
$document['title'] = "Phone";
require_once "resources/paging.php";

// Get the HTTP values and set them as variables
$search = $_GET["search"];
$order_by = $_GET["order_by"] != '' ? $_GET["order_by"] : 'domain_name';
$order = $_GET["order"];

// Get the extension UUID for the current user
$sql = "SELECT extension_uuid FROM v_extension_users WHERE domain_uuid = :domain_uuid AND user_uuid = :user_uuid";
$params = ['domain_uuid' => $domain_uuid, 'user_uuid' => $_SESSION['user_uuid']];
$database = new database;
$extension_uuid = $database->select($sql, $params, 'column');

// Verify that the extension UUID was retrieved
if (!$extension_uuid) {
    echo "Extension UUID not found for this user.";
    exit;
}

// Get the extension and password based on the extension UUID
$sql = "SELECT extension, password FROM v_extensions WHERE extension_uuid = :extension_uuid";
$params = ['extension_uuid' => $extension_uuid];
$row = $database->select($sql, $params, 'all');

// Verify that the extension and password were retrieved
if (empty($row)) {
    echo "Extension data not found.";
    exit;
}

$extension = $row[0]['extension'];
$password = $row[0]['password'];

// Get the contact name for the user
$sql = "SELECT contact_name FROM view_users WHERE domain_name = :domain_name AND username = :username";
$params = ['domain_name' => $_SESSION['domain_name'], 'username' => $_SESSION['username']];
$contactName = $database->select($sql, $params, 'column');

// Use extension as contact name if contact name is empty
if (empty($contactName)) {
    $contactName = $extension;
}

// Display the embedded phone interface
echo "<iframe src='https://" . htmlspecialchars($_SESSION['domain_name']) . "/Browser-Phone/Phone/index.php?server=" . htmlspecialchars($_SESSION['domain_name']) . "&extension=" . htmlspecialchars($extension) . "&password=" . htmlspecialchars($password) . "&fullname=" . urlencode($contactName) . "' width='100%' height='100%' frameborder='none'></iframe>";
echo "<br /><br />";

// Include the footer
require_once "resources/footer.php";
?>
