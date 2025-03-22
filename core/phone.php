
<?php
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
//require_once "resources/paging.php";

//redirect admin to app instead
if (file_exists($_SERVER["PROJECT_ROOT"] . "/app/domains/app_config.php") && !permission_exists('domain_all')) {
    header("Location: " . PROJECT_PATH . "/app/domains/domains.php");
}

//check permission
if (permission_exists('voicemail_greeting_view') || permission_exists('xml_cdr_view')) {
    //access granted
} else {
    echo "access denied";
    exit;
}


//add multi-lingual support
$language = new text;
$text = $language->get();

//change the domain
if (is_uuid($_GET["domain_uuid"]) && $_GET["domain_change"] == "true") {
    if (permission_exists('domain_select')) {
        //get the domain_uuid
        $sql = "select * from v_domains ";
        // $sql .= "order by domain_name asc ";
        $database = new database;
        $result = $database->select($sql, null, 'all');
        if (is_array($result) && sizeof($result) != 0) {
            foreach ($result as $row) {
                if (count($result) == 0) {
                    $_SESSION["domain_uuid"] = $row["domain_uuid"];
                    $_SESSION["domain_name"] = $row['domain_name'];
                } else {
                    if ($row['domain_name'] == $domain_array[0] || $row['domain_name'] == 'www.' . $domain_array[0]) {
                        $_SESSION["domain_uuid"] = $row["domain_uuid"];
                        $_SESSION["domain_name"] = $row['domain_name'];
                    }
                }
            }
        }
        unset($sql, $result);

        //update the domain session variables
        $domain_uuid = $_GET["domain_uuid"];
        $_SESSION['domain_uuid'] = $domain_uuid;
        $_SESSION["domain_name"] = $_SESSION['domains'][$domain_uuid]['domain_name'];
        $_SESSION['domain']['template']['name'] = $_SESSION['domains'][$domain_uuid]['template_name'];

        //clear the extension array so that it is regenerated for the selected domain
        unset($_SESSION['extension_array']);

        //set the setting arrays
        $domain = new domains();
        $domain->db = $db;
        $domain->set();

        //redirect the user
        if ($_SESSION["login"]["destination"] != '') {
            // to default, or domain specific, login destination
            header("Location: " . PROJECT_PATH . $_SESSION["login"]["destination"]["url"]);
        } else {
            header("Location: " . PROJECT_PATH . "/core/user_settings/user_dashboard.php");
        }
        exit;
    }
}

//redirect the user
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/app/domains/domains.php")) {
    $href = '/app/domains/domains.php';
}

//includes
echo "<style> .card{height:90vh;} </style>";
require_once "resources/header.php";
$document['title'] = "WebPhone";
require_once "resources/paging.php";

//get the http values and set them as variables
$search = $_GET["search"];
$order_by = $_GET["order_by"] != '' ? $_GET["order_by"] : 'domain_name';
$order = $_GET["order"];

 $sql = "SELECT extension_uuid FROM v_extension_users WHERE domain_uuid = '" . $domain_uuid . "' AND user_uuid = '".$_SESSION['user_uuid']."'";

$database = new database;
$extension_uuid = $database->select($sql, null, 'column');
unset($sql);

 $sql = "SELECT extension, password FROM v_extensions WHERE extension_uuid = '".$extension_uuid."'";
$database = new database;
$row = $database->select($sql, null, 'all');
 $extension = $row[0]['extension'];
 $password = $row[0]['password'];
unset($sql);

$sql = "SELECT contact_name from view_users where domain_name = '$_SESSION[domain_name]' AND username = '$_SESSION[username]'";
$database = new database;
$contactName = $database->select($sql, null, 'column');
 if($contactName == ""){
    $contactName = $extension;
 }
unset($sql);



//get the domains

$c = 0;
$row_style["0"] = "row_style0";
$row_style["1"] = "row_style1";

//show the header and the search

echo <<<END

<!DOCTYPE html>


    <head>

        <title>WebPhone</title>

        <meta name="description" content="Thank You for using WebPhone.">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
        <meta http-equiv="Expires" content="600"/>

        <link rel="icon" href="favicon.ico">

        <!-- You Ouwn Scripts -->

        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.css"/>

        <style>

            body{
                font-family: Arial, Helvetica, sans-serif;
            }
            p{
                text-align: center;
            }
            .ui-dialog .ui-dialog-content{
                padding: 0px !important;
                overflow: hidden !important;
            }

        </style>

        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-3.3.1.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.js"></script>
        <script lang="javascript">

            const webPort = '9443';
            const webPath = '/wss';

           //added autoload
           //$(document).ready(OpenAsWindow());

           function OpenAsWindow(){

                let windowObj = null;
                let width = 380;
                let height = 620;


                if(windowObj != null){

                    windowObj.dialog("close");
                    windowObj = null;

                }

                var fqdn = $(location).attr('hostname');
                var iframe = $('<iframe/>');
                iframe.css("width", "100%");
                iframe.css("height", "100%");
                iframe.attr("frameborder", "0");
                iframe.attr("src", "https://$_SESSION[domain_name]/core/phone/index.php?server=$_SESSION[domain_name]&extension=$extension&password=$password&fullname=$contactName");

                // Create Window

                windowObj = $('<div/>').html(iframe).dialog({

                    autoOpen: false,
                    title: "Genius SoftPhone",
                    modal: false,
                    width: width,
                    height: height,
                    resizable: true,

                    close: function(event, ui) {

                        $(this).dialog("destroy");
                        windowObj = null;

                    }

                });

                windowObj.dialog("open");

                var windowWidth = $(window).outerWidth();
                var windowHeight = $(window).outerHeight();
                var offsetTextHeight = windowObj.parent().outerHeight();


                windowObj.parent().css('left', windowWidth/2 - width/2 + 'px');
                windowObj.parent().css('top', windowHeight/2 - offsetTextHeight/2 + 'px');


                if(windowWidth <= width) {

                    windowObj.parent().css('left', '0px');

                }

                if(windowHeight <= offsetTextHeight) {

                    windowObj.parent().css('top', '0px');

                }

            }

        </script>

    </head>

        <div id=WebPhone>
            <p>
            <button onclick="OpenAsWindow()">Launch WebPhone</button>
            </p>
        </div>
END;

?>
