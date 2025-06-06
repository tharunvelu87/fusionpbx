<?php

// Unique Calls Dashboard Widget Config

$x = 0;
$array['dashboard'][$x]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db'; // ← Widget UUID
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
$array['dashboard'][$x]['dashboard_chart_type'] = 'number'; // or 'doughnut', 'icon'
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
$array['dashboard'][$x]['dashboard_details_state'] = 'visible'; // or 'hidden'
$array['dashboard'][$x]['dashboard_order'] = '120';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Displays unique inbound calls based on longest call duration in last 24 hours.';

// Dashboard group permissions
$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'e4054bae-108a-48f1-9a9e-35f7d464af4c'; // superadmin
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'cc976e00-66c4-498d-8a97-383b5ee9dc80'; // admin
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '23e473d3-5eb6-4289-beb4-89f37d365ea0'; // user
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '9f761f84-92b6-44cf-9c93-34a0f18480db';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';

?>