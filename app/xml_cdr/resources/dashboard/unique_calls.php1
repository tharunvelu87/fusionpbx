<?php
// Start output buffering
ob_start();

// Includes
require_once dirname(__DIR__, 4) . "/resources/require.php";
require_once "resources/check_auth.php";

// Check permission
if (!permission_exists('xml_cdr_view')) {
    echo "access denied";
    exit;
}

// Initialize database connection
$database = new database;

// Pagination settings
$limit = 500;  // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// SQL query to pull the total count of CDR records
$total_sql = "
    SELECT COUNT(*) as total
    FROM v_xml_cdr
    WHERE domain_uuid = :domain_uuid";
$total_parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$total_result = $database->select($total_sql, $total_parameters, 'row');
$total_calls = $total_result['total'];

// SQL query to pull all CDR details for pagination
$sql = "
    SELECT
        start_stamp,
        caller_id_name,
        caller_id_number,
        destination_number,
        hangup_cause,
        duration,
        direction,
        sip_hangup_disposition,
        bridge_uuid,
        extension_uuid
    FROM
        v_xml_cdr
    WHERE
        domain_uuid = :domain_uuid
    ORDER BY start_stamp DESC
    LIMIT :limit OFFSET :offset";

$parameters = [
    'domain_uuid' => $_SESSION['domain_uuid'],
    'limit' => $limit,
    'offset' => $offset
];

$cdr_records = $database->select($sql, $parameters, 'all');

// Function to determine call status
function get_call_status($record) {
    if (!empty($record['bridge_uuid'])) {
        return 'Answered';
    } else {
        return 'Missed';
    }
}

// Get extension that answered based on extension_uuid
function get_extension($extension_uuid) {
    if (!empty($extension_uuid)) {
        global $database;
        $sql = "SELECT extension FROM v_extensions WHERE extension_uuid = :extension_uuid";
        $parameters['extension_uuid'] = $extension_uuid;
        $result = $database->select($sql, $parameters, 'row');
        return $result['extension'] ?? 'Unknown';
    }
    return 'Missed';
}

// Calculating call statistics
$total_answered = 0;
$total_missed = 0;

foreach ($cdr_records as $record) {
    $call_status = get_call_status($record);
    if ($call_status == 'Answered') {
        $total_answered++;
    } else {
        $total_missed++;
    }
}

// CSV Export with Export Date in Filename
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Clear all output buffers before exporting CSV
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Add the current date to the filename
    $export_date = date('Y-m-d');
    $filename = "cdr_export_{$export_date}.csv";

    header('Content-Type: text/csv');
    header("Content-Disposition: attachment;filename={$filename}");
    $output = fopen('php://output', 'w');

    // Write the CSV header row (without 'Answer Time')
    fputcsv($output, ['Start Time (IST)', 'Caller Name', 'Caller Number', 'Destination', 'Hangup Cause', 'Duration (s)', 'Direction', 'SIP Disposition', 'Status', 'Answered Extension']);

    // Write the CDR data rows
    foreach ($cdr_records as $record) {
        $call_status = get_call_status($record);
        $answered_extension = get_extension($record['extension_uuid']);

        // Convert start_stamp to IST
        $start_stamp = new DateTime($record['start_stamp'], new DateTimeZone('UTC')); // Assuming the input is in UTC
        $start_stamp->setTimezone(new DateTimeZone('Asia/Kolkata')); // Convert to IST
        $formatted_start_stamp = $start_stamp->format('Y-m-d H:i:s');

        // Write row to CSV
        fputcsv($output, [
            $formatted_start_stamp,
            $record['caller_id_name'],
            $record['caller_id_number'],
            $record['destination_number'],
            $record['hangup_cause'],
            $record['duration'],
            $record['direction'],
            $record['sip_hangup_disposition'],
            $call_status,
            $answered_extension
        ]);
    }

    fclose($output);
    exit; // Exit after CSV export to prevent further output
}

// End the output buffering and output the HTML if not exporting
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDR Detailed with Pagination & Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Chart.js library -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .card {
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9em; /* Smaller font size for modern look */
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .answered {
            background-color: #d4edda; /* Light green for answered */
            color: #155724; /* Dark green text */
        }
        .missed {
            background-color: #f8d7da; /* Light red for missed */
            color: #721c24; /* Dark red text */
        }
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        .pagination a {
            margin: 0 5px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #007bff;
            color: #007bff;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
        }
        .pagination a:hover {
            background-color: #0056b3;
            color: white;
        }
        canvas {
            max-width: 600px;
            margin: 0 auto;
        }
        .iframe-container {
            margin-top: 40px;
        }
        iframe {
            width: 100%;
            height: 600px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    <!-- Call Statistics Summary -->
    <div class="summary">
        <div class="card">
            <strong>Total Calls:</strong> <?= $total_calls ?>
        </div>
        <div class="card">
            <strong>Answered Calls:</strong> <?= $total_answered ?>
        </div>
        <div class="card">
            <strong>Missed Calls:</strong> <?= $total_missed ?>
        </div>
    </div>

    <!-- Export CSV Button -->
    <div class="summary" style="justify-content: center;">
        <a href="?export=csv" class="export-link" style="text-decoration: none; padding: 10px 20px; background-color: #007bff; color: white; border-radius: 4px;">Export as CSV</a>
    </div>

    <!-- Graphs -->
    <canvas id="callStatsChart"></canvas>

    <!-- CDR Table -->
    <table>
        <tr>
            <th>Date/Time</th>
            <th>Caller Number</th>
            <th>Destination</th>
            <th>Duration (s)</th>
            <th>Direction</th>
            <th>Status</th>
            <th>Extension</th>
        </tr>
        <?php if ($total_calls > 0): ?>
        <?php foreach ($cdr_records as $record): ?>
        <?php
            $call_status = get_call_status($record);
            $row_class = ($call_status === 'Answered') ? 'answered' : 'missed';
            $answered_extension = get_extension($record['extension_uuid']);
            $start_stamp = new DateTime($record['start_stamp'], new DateTimeZone('UTC'));
            $start_stamp->setTimezone(new DateTimeZone('Asia/Kolkata')); // Convert to IST
            $formatted_start_stamp = $start_stamp->format('Y-m-d H:i:s');
        ?>
        <tr class="<?= $row_class ?>">
            <td><?= htmlspecialchars($formatted_start_stamp) ?></td>
            <td><?= htmlspecialchars($record['caller_id_number']) ?></td>
            <td><?= htmlspecialchars($record['destination_number']) ?></td>
            <td><?= htmlspecialchars($record['duration']) ?></td>
            <td><?= htmlspecialchars($record['direction']) ?></td>
            <td><?= htmlspecialchars($call_status) ?></td>
            <td><?= htmlspecialchars($answered_extension) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr>
            <td colspan="7">No CDR records found.</td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Pagination Links -->
    <div class="pagination">
        <?php for ($i = 1; $i <= ceil($total_calls / $limit); $i++): ?>
        <a href="?page=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>



    <script>
        const ctx = document.getElementById('callStatsChart').getContext('2d');
        const callStatsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Answered', 'Missed'],
                datasets: [{
                    data: [<?= $total_answered ?>, <?= $total_missed ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Call Statistics'
                    }
                }
            },
        });
    </script> 
</body>
</html>
