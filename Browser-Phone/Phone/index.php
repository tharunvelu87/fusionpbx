<!DOCTYPE html>
<html>
    <head>
        <title>Browser Phone</title>
        <meta name="description" content="Browser Phone is a fully featured browser based WebRTC SIP phone for Asterisk. Designed to work with Asterisk PBX. It will connect to Asterisk PBX via web socket, and register an extension.  Calls are made between contacts, and a full call detail is saved. Audio and Video Calls can be recorded locally.">

        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no"/>
        <meta name="HandheldFriendly" content="true">

        <meta name="format-detection" content="telephone=no"/>
        <meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE"/>
        <meta name="apple-mobile-web-app-capabale" content="yes"/>
        
        <meta http-equiv="Pragma" content="no-cache"/>
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate"/>
        <meta http-equiv="Expires" content="0"/>

        <link rel="icon" href="favicon.ico">

        <!-- Styles -->
        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/Normalize/normalize-v8.0.1.css"/>
        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/fonts/font_roboto/roboto.css"/>
        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/fonts/font_awesome/css/font-awesome.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://dtd6jl0d42sve.cloudfront.net/lib/Croppie/Croppie-2.6.4/croppie.css"/>
        <link rel="stylesheet" type="text/css" href="phone.css"/>
        <script type="text/javascript">
            // Provision runtime options can go here.
            var phoneOptions = {
                loadAlternateLang: true
            }

            var web_hook_on_transportError = function(t, ua){
                // console.warn("web_hook_on_transportError",t, ua);
            }
            var web_hook_on_register = function(ua){
                // console.warn("web_hook_on_register", ua);
            }
            var web_hook_on_registrationFailed = function(e){
                // console.warn("web_hook_on_registrationFailed", e);
            }
            var web_hook_on_unregistered = function(){
                // console.warn("web_hook_on_unregistered");
            }
            var web_hook_on_invite = function(session){
                // console.warn("web_hook_on_invite", session);
            }
            var web_hook_on_message = function(message){
                // console.warn("web_hook_on_message", message);
            }
            var web_hook_on_modify = function(action, session){
                // console.warn("web_hook_on_modify", action, session);
            }
            var web_hook_on_dtmf = function(item, session){
                // console.warn("web_hook_on_dtmf", item, session);
            }
            var web_hook_on_terminate = function(session){
                // console.warn("web_hook_on_terminate", session);
            }
        </script>
    </head>

    <body>
        <!-- Loading Animation -->
        <div class=loading>
            <span class="fa fa-circle-o-notch fa-spin"></span>
        </div>

        <!-- The Phone -->
        <div id=Phone></div>

        <!-- Scripts -->
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-3.3.1.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery.md5-min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/jquery/jquery-ui.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/Chart/Chart.bundle-2.7.2.js"></script>
        <script type="text/javascript" src="../lib/SipJS/sip.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/FabricJS/fabric-2.4.6.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/Moment/moment-with-locales-2.24.0.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/Croppie/Croppie-2.6.4/croppie.min.js"></script>
        <script type="text/javascript" src="https://dtd6jl0d42sve.cloudfront.net/lib/XMPP/strophe-1.4.1.umd.min.js"></script>
        <script type="text/javascript">
            const server = "<?php echo $_GET['server'] ?>";
            const webPort = '7443';
            const webPath = '/wss';
            const extension = "<?php echo $_GET['extension'] ?>";
            const password = "<?php echo $_GET['password'] ?>";
            const fullname = "<?php echo $_GET['fullname'] ?>";
        </script>
        <script type="text/javascript" src="phone.js"></script>
    </body>
</html>
