<?php
/**
 * Standalone Browser Phone Interface
 *
 * This file is a combined version that loads FusionPBX’s header and footer.
 * It auto-loads the current user’s phone credentials (extension, password,
 * full name) from the database and embeds the Browser-Phone interface in an iframe.
 * Keeps the session alive, disables navigation without confirmation,
 * and handles standby/wake events to re-activate the page.
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
<div style="height: 80vh;">
    <iframe id="browserPhoneIframe"
            src="https://<?php echo $_SESSION['domain_name']; ?>/Browser-Phone/Phone/index.php?server=<?php echo $_SESSION['domain_name']; ?>&extension=<?php echo $extension; ?>&password=<?php echo $password; ?>&fullname=<?php echo urlencode($contactName); ?>"
            width="100%"
            height="100%"
            frameborder="0"></iframe>
</div>

<script type="text/javascript">
  // Custom beforeunload handler
  function beforeUnloadHandler(e) {
    var confirmationMessage = 'Warning: Leaving this page will terminate your phone session.';
    e.returnValue = confirmationMessage;
    return confirmationMessage;
  }
  window.addEventListener('beforeunload', beforeUnloadHandler);

  // Disable back/forward navigation
  history.pushState(null, null, location.href);
  window.addEventListener('popstate', function(e) {
    history.pushState(null, null, location.href);
    showLeaveConfirmation(e);
  });

  // Intercept all link clicks
  document.addEventListener('click', function(e) {
    var target = e.target;
    while (target && target.tagName !== 'A') {
      target = target.parentElement;
    }
    if (target && target.tagName === 'A') {
      e.preventDefault();
      showLeaveConfirmation(e, target.href);
    }
  });

  // Show confirmation dialog when attempting to leave
  function showLeaveConfirmation(e, href) {
    var msg = 'Warning: Leaving this page will terminate your phone session. Continue?';
    if (confirm(msg)) {
      window.removeEventListener('beforeunload', beforeUnloadHandler);
      if (href) {
        window.location.href = href;
      }
    }
  }

  // Keep session alive by pinging the server every 5 minutes
  setInterval(function() {
    fetch(window.location.href, { method: 'HEAD', credentials: 'same-origin' });
  }, 300000);

  // Handle page visibility change (standby/idle wake)
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      alert('Your phone session is active again.');
      // Refocus on iframe
      var iframe = document.getElementById('browserPhoneIframe');
      if (iframe) iframe.contentWindow.focus();
    }
  });
</script>

<?php
// Include FusionPBX footer.
require_once "resources/footer.php";
?>