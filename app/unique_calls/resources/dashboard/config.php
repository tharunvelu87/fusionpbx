<?php

// =========================
// 1. Declare the App
// =========================

$z = 0;
$apps[$z]['name'] = 'Unique Calls';
$apps[$z]['uuid'] = '6b6adaba-f059-445c-95e4-ecf2761305fc'; // unique app UUID
$apps[$z]['category'] = 'Dashboard';
$apps[$z]['subcategory'] = '';
$apps[$z]['version'] = '1.0';
$apps[$z]['license'] = 'MPL 1.1';
$apps[$z]['url'] = '';
$apps[$z]['description']['en-us'] = 'Widget showing unique inbound calls with longest duration.';

// =========================
// 2. Dashboard Widget Definition
// =========================

$x = 0;
$array['dashboard'][$x]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_name'] = 'Unique Calls';
$array['dashboard'][$x]['dashboard_path'] = 'unique_calls/unique_calls';
$array['dashboard'][$x]['dashboard_icon'] = 'fa-user-check';
$array['dashboard'][$x]['dashboard_icon_color'] = '#1e88e5';
$array['dashboard'][$x]['dashboard_url'] = '/app/unique_calls/resources/dashboard/unique_calls.php';
$array['dashboard'][$x]['dashboard_target'] = 'self';
$array['dashboard'][$x]['dashboard_width'] = '';
$array['dashboard'][$x]['dashboard_height'] = '';
$array['dashboard'][$x]['dashboard_content'] = '';
$array['dashboard'][$x]['dashboard_content_text_align'] = 'center';
$array['dashboard'][$x]['dashboard_content_details'] = '';
$array['dashboard'][$x]['dashboard_chart_type'] = 'number';
$array['dashboard'][$x]['dashboard_label_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_label_text_color'] = '#444444';
$array['dashboard'][$x]['dashboard_label_text_color_hover'] = '';
$array['dashboard'][$x]['dashboard_label_background_color'] = '';
$array['dashboard'][$x]['dashboard_label_background_color_hover'] = '';
$array['dashboard'][$x]['dashboard_number_text_color'] = '#ffffff';
$array['dashboard'][$x]['dashboard_number_text_color_hover'] = '';
$array['dashboard'][$x]['dashboard_number_background_color'] = '#4caf50';
$array['dashboard'][$x]['dashboard_background_color'] = '#ffffff';
$array['dashboard'][$x]['dashboard_background_color_hover'] = '';
$array['dashboard'][$x]['dashboard_detail_background_color'] = '#f9f9f9';
$array['dashboard'][$x]['dashboard_column_span'] = '1';
$array['dashboard'][$x]['dashboard_row_span'] = '1';
$array['dashboard'][$x]['dashboard_details_state'] = 'visible';
$array['dashboard'][$x]['dashboard_order'] = '120';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Displays unique inbound calls based on longest call duration in last 24 hours.';

// =========================
// 3. Permissions
// =========================

$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '931f7a2f-43fb-4d3d-8e39-e546a82206fa';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;

$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '47f4f687-d346-4614-b61f-fcf730f56d53';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;

$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'ab2dd268-057d-419d-bf5a-1d3730d60daa';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';

?>