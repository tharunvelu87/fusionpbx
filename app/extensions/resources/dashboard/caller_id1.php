<?php
/*
	FusionPBX Dashboard Widget: Dynamic Caller ID Selector
	Version: MPL 1.1

	This widget lets you upload a list of caller IDs and dynamically select
	one to update your extension's outbound caller ID via AJAX.
	A green tick appears beside the selected caller ID once the update is successful.
*/

// Include required FusionPBX files.
require_once dirname(__DIR__, 4) . "/resources/require.php";
require_once "resources/check_auth.php";

// Check permissions.
if (!permission_exists('extension_caller_id')) {
    echo "access denied";
    exit;
}

// Start session if not already started.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// -------------------------------------------------------------------------
// AJAX ENDPOINTS (no headers/footers in a widget)
// Process AJAX requests if the "ajax" parameter is set.
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    $response = [];

    if ($action == 'upload_list') {
        // Process the uploaded caller IDs.
        $caller_ids_str = trim($_POST['caller_ids'] ?? '');
        if (empty($caller_ids_str)) {
            $response['status'] = 'error';
            $response['message'] = 'No caller IDs provided.';
        } else {
            // Split the string by newline or commas, trim, and filter out empty values.
            $ids = preg_split('/[\n,]+/', $caller_ids_str);
            $ids = array_map('trim', $ids);
            $ids = array_filter($ids, function($id) { return $id !== ''; });
            $_SESSION['caller_ids'] = array_values($ids);
            $response['status'] = 'ok';
            $response['caller_ids'] = $_SESSION['caller_ids'];
            $response['message'] = 'Caller IDs uploaded successfully.';
        }
        echo json_encode($response);
        exit;
    }
    elseif ($action == 'update_callerid') {
        // Process the caller ID update request.
        $caller_id = trim($_POST['caller_id'] ?? '');
        if (empty($caller_id)) {
            $response['status'] = 'error';
            $response['message'] = 'Invalid caller ID.';
        }
        else {
            // Assuming the first extension in the session is to be updated.
            if (isset($_SESSION['user']['extension'][0]['extension_uuid'])) {
                $extension_uuid = $_SESSION['user']['extension'][0]['extension_uuid'];
                $database = new database;
                // Update both outbound_caller_id_number and outbound_caller_id_name.
                $sql = "UPDATE v_extensions 
                        SET outbound_caller_id_number = :caller_id,
                            outbound_caller_id_name   = :caller_id
                        WHERE extension_uuid         = :extension_uuid";
                $parameters = [
                    ':caller_id'      => $caller_id,
                    ':extension_uuid' => $extension_uuid
                ];
                try {
                    $database->execute($sql, $parameters);

                    // Clear the cache for this extension.
                    $cache = new cache;
                    if (isset($_SESSION['user']['extension'][0]['destination']) && isset($_SESSION['user']['extension'][0]['user_context'])) {
                        $destination  = $_SESSION['user']['extension'][0]['destination'];
                        $user_context = $_SESSION['user']['extension'][0]['user_context'];
                        $cache->delete("directory:" . $destination . "@" . $user_context);
                    }

                    // Update session variables.
                    foreach ($_SESSION['user']['extension'] as &$ext) {
                        if ($ext['extension_uuid'] == $extension_uuid) {
                            $ext['outbound_caller_id_number'] = $caller_id;
                            $ext['outbound_caller_id_name']   = $caller_id;
                        }
                    }

                    error_log("Caller ID updated for extension $extension_uuid to $caller_id");

                    $response['status']   = 'ok';
                    $response['message']  = "Caller ID updated to $caller_id.";
                    $response['selected'] = $caller_id;
                }
                catch (Exception $e) {
                    error_log("Database update failed: " . $e->getMessage());
                    $response['status']  = 'error';
                    $response['message'] = "Database update failed: " . $e->getMessage();
                }
            }
            else {
                $response['status']  = 'error';
                $response['message'] = "Extension not found.";
            }
        }
        echo json_encode($response);
        exit;
    }
}
?>

<!-- Widget HTML output (no header or footer) -->
<!-- HUD Widget Container -->
<div class="hud_box" id="caller_id_widget">

    <!-- Collapsed Section (shows selected caller ID or placeholder) -->
    <div class="hud_content"
         onclick="$('#caller_id_details').slideToggle('fast'); toggle_grid_row_end('<?php echo $dashboard_name ?? ''; ?>');">
        <!-- Title -->
        <span class="hud_title">Caller ID Selector</span>
        <?php
        $current_cid = null;
        if (isset($_SESSION['user']['extension'][0]['outbound_caller_id_number']) && $_SESSION['user']['extension'][0]['outbound_caller_id_number'] != '') {
            $current_cid = $_SESSION['user']['extension'][0]['outbound_caller_id_number'];
        }
        ?>
        <!-- Display selected Caller ID -->
        <span class="hud_stat" id="current_caller_id">
            <?php echo $current_cid ? escape($current_cid) : 'No Caller ID chosen'; ?>
        </span>
        <!-- Expander Icon -->
        <span class="hud_expander"><span class="fas fa-ellipsis-h"></span></span>
    </div>

    <!-- Expanded Section (hidden by default) -->
    <div class="hud_details hud_box" id="caller_id_details" style="display: none; padding: 10px;">
        <!-- Caller ID List -->
        <h4>Available Caller IDs:</h4>
        <div id="caller_id_list">
            <?php
            if (isset($_SESSION['caller_ids']) && is_array($_SESSION['caller_ids']) && count($_SESSION['caller_ids']) > 0) {
                foreach ($_SESSION['caller_ids'] as $cid) {
                    echo "<div class='caller-id-item' data-caller_id='" . htmlspecialchars($cid) . "'>" . htmlspecialchars($cid) . "</div>";
                }
            }
            else {
                echo "<p>No caller IDs uploaded.</p>";
            }
            ?>
        </div>

        <div id="update_status" style="margin-top:10px; color:green;"></div>

        <hr style="margin: 15px 0;">

        <!-- Upload Form for new Caller IDs -->
        <p>Upload a list of caller IDs (comma/newline separated):</p>
        <form id="upload_form" onsubmit="return false;">
            <textarea name="caller_ids" id="caller_ids" style="width:100%; height:80px; font-family:monospace;"><?php
                if (isset($_SESSION['caller_ids']) && is_array($_SESSION['caller_ids'])) {
                    echo implode(", ", $_SESSION['caller_ids']);
                }
            ?></textarea>
            <br>
            <button type="button" id="upload_btn" style="margin-top:5px;">Upload Caller IDs</button>
        </form>
        <div id="upload_status" style="margin-top:10px; color:green;"></div>
    </div>
</div>

<!-- Minimal styling for caller-id items (in addition to HUD styling) -->
<style>
    .caller-id-item {
        padding: 5px; 
        margin: 2px 0; 
        border: 1px solid #ddd; 
        cursor: pointer; 
        position: relative;
    }
    .caller-id-item:hover {
        background-color: #f9f9f9;
    }
    .selected-caller-id {
        color: green; 
        font-weight: bold;
    }
    .selected-caller-id i {
        position: absolute; 
        right: 10px; 
        top: 50%; 
        transform: translateY(-50%);
    }
</style>

<!-- jQuery & FontAwesome (for tick icon) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script>
// In your AJAX calls, we reference the file explicitly using PROJECT_PATH.
var widgetUrl = "<?php echo PROJECT_PATH; ?>/app/extensions/resources/dashboard/caller_id1.php";

$(document).ready(function(){

    // Upload caller IDs
    $("#upload_btn").click(function(){
        var caller_ids = $("#caller_ids").val();
        $.ajax({
            url: widgetUrl + "?ajax=upload_list",
            type: "POST",
            dataType: "json",
            data: { caller_ids: caller_ids },
            success: function(response) {
                if(response.status === "ok") {
                    $("#upload_status").css("color","green").text(response.message);
                    var listHtml = "";
                    $.each(response.caller_ids, function(index, cid){
                        listHtml += "<div class='caller-id-item' data-caller_id='" + cid + "'>" + cid + "</div>";
                    });
                    $("#caller_id_list").html(listHtml);
                } else {
                    $("#upload_status").css("color","red").text(response.message);
                }
            },
            error: function(){
                $("#upload_status").css("color","red").text("Error uploading caller IDs.");
            }
        });
    });

    // Update caller ID on selection.
    $("#caller_id_list").on("click", ".caller-id-item", function(){
        var selectedCallerId = $(this).data("caller_id");
        var itemElement = $(this);

        $.ajax({
            url: widgetUrl + "?ajax=update_callerid",
            type: "POST",
            dataType: "json",
            data: { caller_id: selectedCallerId },
            success: function(response) {
                if(response.status === "ok"){
                    $("#update_status").css("color","green").text(response.message);
                    // Remove selection styling from all items.
                    $(".caller-id-item").removeClass("selected-caller-id").each(function(){
                        $(this).html($(this).data("caller_id"));
                    });
                    // Mark the clicked item as selected and append a green tick.
                    itemElement.addClass("selected-caller-id")
                               .html(selectedCallerId + " <i class='fas fa-check'></i>");
                    // Update the HUD display with the new caller ID.
                    $("#current_caller_id").text(selectedCallerId);
                }
                else {
                    $("#update_status").css("color","red").text(response.message);
                }
            },
            error: function(){
                $("#update_status").css("color","red").text("Error updating caller ID.");
            }
        });
    });
});
</script>
