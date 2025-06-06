<?php

$x = 0;
$array['dashboard'][$x]['dashboard_uuid'] = 'e7d5c9f0-3c2a-4d1f-b4ea-6bfb12345678'; // ← generate your own UUID
$array['dashboard'][$x]['dashboard_name'] = 'Active Calls';
$array['dashboard'][$x]['dashboard_path'] = 'calls_active/active_calls';
$array['dashboard'][$x]['dashboard_icon'] = 'fa-phone';
$array['dashboard'][$x]['dashboard_icon_color'] = '#00c853';
$array['dashboard'][$x]['dashboard_url'] = '/app/calls_active/resources/dashboard/active_calls.php';
$array['dashboard'][$x]['dashboard_target'] = 'self';
$array['dashboard'][$x]['dashboard_width'] = '';
$array['dashboard'][$x]['dashboard_height'] = '';
$array['dashboard'][$x]['dashboard_content'] = '';
$array['dashboard'][$x]['dashboard_content_text_align'] = 'center';
$array['dashboard'][$x]['dashboard_content_details'] = '';
$array['dashboard'][$x]['dashboard_chart_type'] = '';
$array['dashboard'][$x]['dashboard_label_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_label_text_color'] = '#444444';
$array['dashboard'][$x]['dashboard_label_text_color_hover'] = '';
$array['dashboard'][$x]['dashboard_label_background_color'] = '';
$array['dashboard'][$x]['dashboard_label_background_color_hover'] = '';
$array['dashboard'][$x]['dashboard_number_text_color'] = '';
$array['dashboard'][$x]['dashboard_number_text_color_hover'] = '';
$array['dashboard'][$x]['dashboard_number_background_color'] = '';
$array['dashboard'][$x]['dashboard_background_color'] = '#ffffff';
$array['dashboard'][$x]['dashboard_background_color_hover'] = '';
$array['dashboard'][$x]['dashboard_detail_background_color'] = '#ffffff';
$array['dashboard'][$x]['dashboard_column_span'] = '1';
$array['dashboard'][$x]['dashboard_row_span'] = '1';
$array['dashboard'][$x]['dashboard_details_state'] = 'hidden';
$array['dashboard'][$x]['dashboard_order'] = '100';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Displays real-time active call information.';

$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '931f7a2f-43fb-4d3d-8e39-e546a82206fa';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'e7d5c9f0-3c2a-4d1f-b4ea-6bfb12345678';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '47f4f687-d346-4614-b61f-fcf730f56d53';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'e7d5c9f0-3c2a-4d1f-b4ea-6bfb12345678';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'ab2dd268-057d-419d-bf5a-1d3730d60daa';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'e7d5c9f0-3c2a-4d1f-b4ea-6bfb12345678';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';
$x++;

?>