<?php
// billing.php – FusionPBX Billing Dashboard & PDF Invoice (Scoped to User)

// 1) DEBUG: show all PHP errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2) FusionPBX bootstrap
require_once dirname(__DIR__,2) . '/resources/require.php';
require_once dirname(__DIR__,2) . '/resources/check_auth.php';

// 3) Permission check
if (!permission_exists('xml_cdr_view')) {
    echo "<div class='alert alert-danger'>Access denied.</div>";
    exit;
}

// 4) Ensure $db
if (!isset($db) || $db === null) {
    require_once dirname(__DIR__,2) . '/resources/classes/database.php';
    $database = new database;
    $database->connect();
    $db = $database->db;
}

// 5) Billing rates (INR/min)
$rate_inbound  = 0.00;   // free inbound
$rate_outbound = 15.00;  // ₹15/min outbound

// 6) Form inputs
$uuids      = $_POST['extensions']    ?? [];
$start_date = $_POST['start_date']    ?? date('Y-m-01');
$end_date   = $_POST['end_date']      ?? date('Y-m-d');
$do_show    = isset($_POST['generate']);
$do_sum     = isset($_POST['export_pdf']);
$do_det     = isset($_POST['export_detailed']);
$run        = $do_show || $do_sum || $do_det;

// 7) Load all extensions
$ext_list = [];
$sth = $db->prepare(
    "SELECT extension_uuid, extension
       FROM v_extensions
      WHERE domain_uuid = :d
   ORDER BY extension"
);
$sth->execute([':d'=>$_SESSION['domain_uuid']]);
while ($r = $sth->fetch(PDO::FETCH_ASSOC)) {
    $ext_list[$r['extension_uuid']] = $r['extension'];
}

// 7.1) Restrict to user's own extensions
$allowed = [];
$sth2 = $db->prepare(
    "SELECT extension_uuid
       FROM v_extension_users
      WHERE domain_uuid = :d
        AND user_uuid = :u"
);
$sth2->execute([
    ':d' => $_SESSION['domain_uuid'],
    ':u' => $_SESSION['user_uuid']
]);
while ($r2 = $sth2->fetch(PDO::FETCH_ASSOC)) {
    $allowed[] = $r2['extension_uuid'];
}
// Filter ext_list
$ext_list = array_intersect_key(
    $ext_list,
    array_flip($allowed)
);
if (empty($ext_list)) {
    echo "<div class='alert alert-danger'>No extensions assigned.</div>";
    exit;
}

// Helper: FusionPBX-styled table
function render_table(array $head, array $rows) {
    $html  = "<div class='table-responsive'><table class='table table-striped table-hover'>\n<thead><tr>";
    foreach ($head as $h) {
        $html .= "<th>" . htmlspecialchars($h) . "</th>";
    }
    $html .= "</tr></thead>\n<tbody>\n";
    foreach ($rows as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
            $html .= "<td>" . htmlspecialchars((string)$cell) . "</td>";
        }
        $html .= "</tr>\n";
    }
    $html .= "</tbody>\n</table></div>\n";
    return $html;
}

// 8) Validate input
if ($run) {
    // ensure selections are within allowed extensions
    $uuids = array_values(array_intersect($uuids, array_keys($ext_list)));
    if (empty($uuids) || !$start_date || !$end_date) {
        echo "<div class='alert alert-warning'>Please select at least one valid extension and both dates.</div>";
        $run = false;
    }
}

// 9) Process report
if ($run) {
    // map UUID -> number
    $selected = [];
    foreach ($uuids as $id) {
        $selected[$id] = $ext_list[$id];
    }
    $sdt = "$start_date 00:00:00";
    $edt = "$end_date   23:59:59";
    $ph  = implode(',', array_fill(0, count($selected), '?'));

    // inbound
    $sql_in = "
      SELECT extension_uuid, ROUND(SUM(billsec)/60.0,2) AS in_min
        FROM v_xml_cdr
       WHERE domain_uuid = ?
         AND extension_uuid IN ($ph)
         AND direction = 'inbound'
         AND start_stamp BETWEEN ? AND ?
    GROUP BY extension_uuid
    ";
    $stmt_in = $db->prepare($sql_in);
    $i=1;
    $stmt_in->bindValue($i++, $_SESSION['domain_uuid']);
    foreach (array_keys($selected) as $id) $stmt_in->bindValue($i++, $id);
    $stmt_in->bindValue($i++, $sdt);
    $stmt_in->bindValue($i++, $edt);
    $stmt_in->execute();
    $usage = [];
    while ($r = $stmt_in->fetch(PDO::FETCH_ASSOC)) {
        $usage[$r['extension_uuid']]['in'] = (float)$r['in_min'];
    }

    // outbound
    $sql_out = "
      SELECT extension_uuid, SUM(CEIL(GREATEST(billsec,1)/60.0)) AS out_min
        FROM v_xml_cdr
       WHERE domain_uuid = ?
         AND extension_uuid IN ($ph)
         AND direction = 'outbound'
         AND start_stamp BETWEEN ? AND ?
    GROUP BY extension_uuid
    ";
    $stmt_out = $db->prepare($sql_out);
    $i=1;
    $stmt_out->bindValue($i++, $_SESSION['domain_uuid']);
    foreach (array_keys($selected) as $id) $stmt_out->bindValue($i++, $id);
    $stmt_out->bindValue($i++, $sdt);
    $stmt_out->bindValue($i++, $edt);
    $stmt_out->execute();
    while ($r = $stmt_out->fetch(PDO::FETCH_ASSOC)) {
        $usage[$r['extension_uuid']]['out'] = (int)$r['out_min'];
    }

    // assemble summary
    $sum_rows = [];
    $totals   = ['in'=>0,'out'=>0,'all'=>0,'chg'=>0];
    foreach ($selected as $uuid=>$num) {
        $in  = $usage[$uuid]['in']  ?? 0;
        $out = $usage[$uuid]['out'] ?? 0;
        $all = $in + $out;
        $chg = ($in*$rate_inbound) + ($out*$rate_outbound);
        $sum_rows[] = [$num, number_format($in,2), number_format($out,0), number_format($all,2), number_format($chg,2)];
        $totals['in']  += $in;
        $totals['out'] += $out;
        $totals['all'] += $all;
        $totals['chg'] += $chg;
    }
    $sum_rows[] = ['Totals', number_format($totals['in'],2), number_format($totals['out'],0), number_format($totals['all'],2), number_format($totals['chg'],2)];

    // PDF CSS
    $pdf_css = <<<CSS
<style>
  body{font-family:Arial,sans-serif;font-size:12px;}
  h1,h2{text-align:center;margin:8px 0;}
  p.info{text-align:center;margin:4px 0 12px;}
  table{width:100%;border-collapse:collapse;margin-bottom:16px;}
  th,td{border:1px solid #444;padding:6px 8px;text-align:left;}
  th{background:#eee;font-weight:bold;}
  tr:nth-child(even){background:#f9f9f9;}
  tfoot td{font-weight:bold;background:#ddd;}
</style>
CSS;

    // Summary PDF
    if ($do_sum) {
        $html = "<h1>GeniusPBX Invoice</h1>"
              . "<p class='info'>info@geniuspbx.com</p>"
              . "<h2>Summary Invoice</h2>"
              . "<p class='info'>Period: <strong>$start_date</strong> to <strong>$end_date</strong></p>"
              . render_table(['Extension','Inbound','Outbound','Total','Charge'], $sum_rows);
        require_once dirname(__DIR__,2) . '/vendor/autoload.php';
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($pdf_css . $html);
        $pdf->setPaper('A4','portrait');
        $pdf->render();
        $pdf->stream("Invoice_{$start_date}_{$end_date}.pdf", ['Attachment'=>true]);
        exit;
    }

    // Detailed PDF
    if ($do_det) {
        $sql_det = "
          SELECT extension_uuid, caller_id_number, destination_number, billsec, start_stamp
            FROM v_xml_cdr
           WHERE domain_uuid = ?
             AND extension_uuid IN ($ph)
             AND start_stamp BETWEEN ? AND ?
        ORDER BY start_stamp
        ";
        $stmt = $db->prepare($sql_det);
        $i=1; $stmt->bindValue($i++, $_SESSION['domain_uuid']);
        foreach (array_keys($selected) as $id) $stmt->bindValue($i++, $id);
        $stmt->bindValue($i++, $sdt); $stmt->bindValue($i++, $edt);
        $stmt->execute();
        $detail_rows = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $min   = $r['billsec']==0 ? 1 : ceil($r['billsec']/60);
            $price = ($r['direction']=='inbound') ? $rate_inbound*$min : $rate_outbound*$min;
            $detail_rows[] = [
                $selected[$r['extension_uuid']],
                $r['caller_id_number'],
                $r['destination_number'],
                number_format($min,2),
                number_format($price,2),
                $r['start_stamp']
            ];
        }
        $html = "<h1>GeniusPBX Invoice</h1>"
              . "<p class='info'>info@geniuspbx.com</p>"
              . "<h2>Detailed CDR Invoice</h2>"
              . "<p class='info'>Period: <strong>$start_date</strong> to <strong>$end_date</strong></p>"
              . render_table(['Extension','Caller ID','Destination','Minutes','Price','Date/Time'], $detail_rows);
        require_once dirname(__DIR__,2) . '/vendor/autoload.php';
        $pdf = new \Dompdf\Dompdf();
        $pdf->loadHtml($pdf_css . $html);
        $pdf->setPaper('A4','portrait');
        $pdf->render();
        $pdf->stream("Detailed_Invoice_{$start_date}_{$end_date}.pdf", ['Attachment'=>true]);
        exit;
    }

    // on-page summary
    $report_html = render_table(['Extension','Inbound','Outbound','Total','Charge'], $sum_rows);
}

// 10) Render Dashboard UI
$document['title'] = "Billing & CDR Invoice";
require_once dirname(__DIR__,2) . '/resources/header.php';
?>
<div class="panel panel-default" style="max-width:800px;margin:20px auto;">
  <div class="panel-heading"><h2 style="margin:0;">GeniusPBX Billing Dashboard</h2></div>
  <div class="panel-body">
    <form method="post" class="form-inline" style="display:flex;gap:10px;align-items:flex-end;">
      <div class="form-group">
        <label>Start Date:</label>
        <input class="formfld datepicker" type="date" name="start_date" value="<?=htmlspecialchars($start_date)?>">
      </div>
      <div class="form-group">
        <label>End Date:</label>
        <input class="formfld datepicker" type="date" name="end_date" value="<?=htmlspecialchars($end_date)?>">
      </div>
      <div class="form-group" style="flex:1;">
        <label>Extensions:</label>
        <select class="formfld" name="extensions[]" multiple style="width:100%;height:100px;">
          <?php foreach ($ext_list as $uuid => $ext): ?>
            <option value="<?=$uuid?>" <?=in_array($uuid,$uuids)?'selected':''?>><?=htmlspecialchars($ext)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <button class="btn btn-default" name="generate">Show Report</button>
        <button class="btn btn-primary" name="export_pdf">Download Invoice</button>
        <button class="btn btn-secondary" name="export_detailed">Download Detailed CDR</button>
      </div>
    </form>
  </div>
  <?php if (!empty($report_html)): ?>
    <div class="panel-body" style="border-top:1px solid #ddd; padding-top:5px;">
      <?=$report_html?>
    </div>
  <?php endif; ?>
</div>
<?php require_once dirname(__DIR__,2) . '/resources/footer.php'; ?>