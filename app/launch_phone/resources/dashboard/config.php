<?php

// Launch Phone Widget
$x++;
$array['dashboard'][$x]['dashboard_uuid'] = '7fb50a22-d5af-4308-9ddd-da3860b0533b'; // unique UUID
$array['dashboard'][$x]['dashboard_name'] = 'Launch Phone';
$array['dashboard'][$x]['dashboard_path'] = 'launch_phone/launch_phone';
$array['dashboard'][$x]['dashboard_icon'] = 'fa-phone-volume';
$array['dashboard'][$x]['dashboard_icon_color'] = '#4caf50';
$array['dashboard'][$x]['dashboard_url'] = '/app/launch_phone/resources/dashboard/launch_phone.php';
$array['dashboard'][$x]['dashboard_target'] = 'self';
$array['dashboard'][$x]['dashboard_width'] = '';
$array['dashboard'][$x]['dashboard_height'] = '';
$array['dashboard'][$x]['dashboard_content'] = '';
$array['dashboard'][$x]['dashboard_content_text_align'] = 'center';
$array['dashboard'][$x]['dashboard_content_details'] = '';
$array['dashboard'][$x]['dashboard_chart_type'] = 'icon';
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
$array['dashboard'][$x]['dashboard_detail_background_color'] = '';
$array['dashboard'][$x]['dashboard_column_span'] = '1';
$array['dashboard'][$x]['dashboard_row_span'] = '1';
$array['dashboard'][$x]['dashboard_details_state'] = 'hidden';
$array['dashboard'][$x]['dashboard_order'] = '80';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Widget to launch WebRTC or SIP phone dialer.';

$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'c7e5e4a8-3958-460d-bcbf-6e0e193eab1d';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '7fb50a22-d5af-4308-9ddd-da3860b0533b';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = '9faddc67-3bcb-4c4c-b6c3-5b2f21042d7a'; // admin
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '7fb50a22-d5af-4308-9ddd-da3860b0533b';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'c9eba2eb-7a24-4b0d-bb6b-f0207cc1c8a3'; // user
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = '7fb50a22-d5af-4308-9ddd-da3860b0533b';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';


// Dialer (FusionPhone) (icon-style)
$x++;
$array['dashboard'][$x]['dashboard_uuid'] = 'a9b8c7d6-e5f4-4a3b-9c2d-1e0f3a4b5c7d';
$array['dashboard'][$x]['dashboard_name'] = 'Dialer';
$array['dashboard'][$x]['dashboard_path'] = 'dashboard/icon';
$array['dashboard'][$x]['dashboard_icon'] = 'fa-phone';
$array['dashboard'][$x]['dashboard_icon_color'] = '#1976d2';
$array['dashboard'][$x]['dashboard_url'] = '/core/phone/fusionphone.php';
$array['dashboard'][$x]['dashboard_target'] = 'self';
$array['dashboard'][$x]['dashboard_width'] = '';
$array['dashboard'][$x]['dashboard_height'] = '';
$array['dashboard'][$x]['dashboard_content'] = '';
$array['dashboard'][$x]['dashboard_content_text_align'] = '';
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
$array['dashboard'][$x]['dashboard_detail_background_color'] = '';
$array['dashboard'][$x]['dashboard_column_span'] = '1';
$array['dashboard'][$x]['dashboard_row_span'] = '1';
$array['dashboard'][$x]['dashboard_details_state'] = 'disabled';
$array['dashboard'][$x]['dashboard_order'] = '55';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Quick access to the FusionPhone dialer.';
$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'f0e1d2c3-b4a5-4678-9d0e-1f2a3b4c5d6e';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'a9b8c7d6-e5f4-4a3b-9c2d-1e0f3a4b5c7d';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'd9c8b7a6-5f4e-3d2c-1b0a-9e8f7d6c5b4a';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'a9b8c7d6-e5f4-4a3b-9c2d-1e0f3a4b5c7d';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'c7b6a5d4-e3f2-1c0b-9a8f-7e6d5c4b3a2f';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'a9b8c7d6-e5f4-4a3b-9c2d-1e0f3a4b5c7d';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';

// Operator Panel (icon-style)
$x++;
$array['dashboard'][$x]['dashboard_uuid'] = 'd1c2b3a4-e5f6-4789-9a0b-c1d2e3f4a5b7';
$array['dashboard'][$x]['dashboard_name'] = 'Operator Panel';
$array['dashboard'][$x]['dashboard_path'] = 'dashboard/icon';
$array['dashboard'][$x]['dashboard_icon'] = 'fa-th-large';
$array['dashboard'][$x]['dashboard_icon_color'] = '#ff9800';
$array['dashboard'][$x]['dashboard_url'] = '/app/basic_operator_panel/index.php';
$array['dashboard'][$x]['dashboard_target'] = 'self';
$array['dashboard'][$x]['dashboard_width'] = '';
$array['dashboard'][$x]['dashboard_height'] = '';
$array['dashboard'][$x]['dashboard_content'] = '';
$array['dashboard'][$x]['dashboard_content_text_align'] = '';
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
$array['dashboard'][$x]['dashboard_detail_background_color'] = '';
$array['dashboard'][$x]['dashboard_column_span'] = '1';
$array['dashboard'][$x]['dashboard_row_span'] = '1';
$array['dashboard'][$x]['dashboard_details_state'] = 'disabled';
$array['dashboard'][$x]['dashboard_order'] = '60';
$array['dashboard'][$x]['dashboard_enabled'] = 'true';
$array['dashboard'][$x]['dashboard_description'] = 'Quick link to the operator panel.';
$y = 0;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'a9e8d7c6-b5a4-4789-0f1e-d2c3b4a5f6e7';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'd1c2b3a4-e5f6-4789-9a0b-c1d2e3f4a5b7';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'superadmin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'b8c7d6e5-f4a3-4920-1d0c-e3f2a1b4c5d6';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'd1c2b3a4-e5f6-4789-9a0b-c1d2e3f4a5b7';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'admin';
$y++;
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_group_uuid'] = 'c6d5e4f3-b2a1-4987-0c1d-e2f3a4b5c6d7';
$array['dashboard'][$x]['dashboard_groups'][$y]['dashboard_uuid'] = 'd1c2b3a4-e5f6-4789-9a0b-c1d2e3f4a5b7';
$array['dashboard'][$x]['dashboard_groups'][$y]['group_name'] = 'user';

?>