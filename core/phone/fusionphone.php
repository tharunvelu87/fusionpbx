<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

/**
 * Standalone Browser Phone Interface with Refresh
 */

require_once 'root.php';
require_once 'resources/require.php';
require_once 'resources/check_auth.php';
require_once 'resources/paging.php';

// Redirect admin without domain_all permission
if (file_exists($_SERVER['PROJECT_ROOT'].'/app/domains/app_config.php') && !permission_exists('domain_all')) {
    header('Location: '.PROJECT_PATH.'/app/domains/domains.php');
    exit;
}

// Permission check
if (!(permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view'))) {
    echo 'access denied';
    exit;
}

// Set the page title and load header
$document['title'] = 'Phone';
require_once 'resources/header.php';

// Database setup
$domain_uuid = $_SESSION['domain_uuid'];
$user_uuid = $_SESSION['user_uuid'];
$db = new database();

// Get extension_uuid
$sql = 'SELECT extension_uuid FROM v_extension_users WHERE domain_uuid=:domain_uuid AND user_uuid=:user_uuid';
$extension_uuid = $db->select($sql, ['domain_uuid'=>$domain_uuid, 'user_uuid'=>$user_uuid], 'column');

// Get extension and password
$sql = 'SELECT extension,password FROM v_extensions WHERE extension_uuid=:extension_uuid';
$row = $db->select($sql, ['extension_uuid'=>$extension_uuid], 'row');
if ($row) {
    $extension = $row['extension'];
    $password  = $row['password'];
} else {
    $extension = '';
    $password  = '';
}

// Get contact name
$sql = 'SELECT contact_name FROM view_users WHERE domain_name=:domain_name AND username=:username';
$contactName = $db->select($sql, ['domain_name'=>$_SESSION['domain_name'], 'username'=>$_SESSION['username']], 'column');
if (empty($contactName)) {
    $contactName = $extension;
}
?>

<!-- Browser Phone iframe -->
<div style="height:80vh">
    <iframe id="browserPhoneIframe" src="https://<?php echo $_SESSION['domain_name']; ?>/Browser-Phone/Phone/index.php?server=<?php echo $_SESSION['domain_name']; ?>&extension=<?php echo $extension; ?>&password=<?php echo $password; ?>&fullname=<?php echo urlencode($contactName); ?>" width="100%" height="100%" frameborder="0"></iframe>
</div>

<!-- Refresh button -->
<button id="refreshButton" style='position:fixed;bottom:10px;right:10px;z-index:1000;padding:8px 12px;background:#007acc;color:#fff;border:none;border-radius:4px;cursor:pointer;'>Refresh</button>

<script>
// Warn before unload
function beforeUnloadHandler(e) {
    var msg = 'Leaving this page will end your phone session.';
    e.returnValue = msg;
    return msg;
}
window.addEventListener('beforeunload', beforeUnloadHandler);

// Disable back/forward navigation
history.pushState(null, null, location.href);
window.addEventListener('popstate', function(e) {
    history.pushState(null, null, location.href);
    showLeaveConfirmation(e);
});

// Intercept all links
document.addEventListener('click', function(e) {
    var t = e.target;
    while (t && t.tagName !== 'A') t = t.parentElement;
    if (t && t.tagName === 'A') {
        e.preventDefault();
        showLeaveConfirmation(e, t.href);
    }
});

// Confirmation dialog
function showLeaveConfirmation(e, href) {
    var msg = 'Leaving will end your phone session. Continue?';
    if (confirm(msg)) {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        if (href) window.location.href = href;
    }
}

// Keep session alive by pinging every 5 minutes
setInterval(function() {
    fetch(location.href, { method:'HEAD', credentials:'same-origin' });
}, 300000);

// Handle visibility change
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        alert('Your phone session is active again.');
        var iframe = document.getElementById('browserPhoneIframe');
        if (iframe) iframe.contentWindow.focus();
    }
});

// Full sign-out and iframe reload
function fullSignOut() {
    document.cookie.split(';').forEach(function(c) {
        var n = c.split('=')[0].trim();
        if (n) document.cookie = n + '=;path=/Browser-Phone;expires=Thu,01 Jan 1970 00:00:00 GMT';
    });
    try {
        var w = document.getElementById('browserPhoneIframe').contentWindow;
        w.localStorage.clear(); w.sessionStorage.clear();
    } catch (e) {}
    var iframe = document.getElementById('browserPhoneIframe');
    iframe.src = iframe.src;
}

// Bind Refresh button
document.getElementById('refreshButton').addEventListener('click', function() {
    if (confirm('This will sign you out and refresh your phone session. Continue?')) {
        fullSignOut();
    }
});
</script>

<?php
// Footer
require_once 'resources/footer.php';
?>