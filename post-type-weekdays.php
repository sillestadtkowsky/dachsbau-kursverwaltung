<?php
//custom post type - weekdays
function timetable_weekdays_init()
{
	$labels = array(
		'name' => _x('Timetable columns', 'post type general name', 'timetable'),
		'singular_name' => _x('Timetable Column', 'post type singular name', 'timetable'),
		'add_new' => _x('Add New', 'timetable_weekdays', 'timetable'),
		'add_new_item' => esc_html__('Add New Timetable Column', 'timetable'),
		'edit_item' => esc_html__('Edit Timetable Column', 'timetable'),
		'new_item' => esc_html__('New Timetable Column', 'timetable'),
		'all_items' => esc_html__('All Timetable Columns', 'timetable'),
		'view_item' => esc_html__('View Timetable Column', 'timetable'),
		'search_items' => esc_html__('Search Timetable Columns', 'timetable'),
		'not_found' =>  esc_html__('No timetable columns found', 'timetable'),
		'not_found_in_trash' => esc_html__('No timetable columns found in Trash', 'timetable'), 
		'parent_item_colon' => '',
		'menu_name' => esc_html__("Timetable columns", 'timetable')
	);
	$args = array(  
		"labels" => $labels, 
		"public" => false,  
		"show_ui" => true,  
		"capability_type" => "post",  
		"menu_position" => 20,
		"hierarchical" => false,  
		"rewrite" => true,  
		"supports" => array("title", "page-attributes")
	);
	register_post_type("timetable_weekdays", $args);
}  
add_action("init", "timetable_weekdays_init"); 

//custom weekdays items list
function timetable_weekdays_edit_columns($columns)
{
	$columns = array(
		"cb" => "<input type=\"checkbox\" />",
		"title" => esc_html__('Day name', 'timetable'),
		"date" => esc_html__('Date', 'timetable')
	);

	return $columns;
}  
add_filter("manage_edit-timetable_weekdays_columns", "timetable_weekdays_edit_columns");

//autoincrementing order value for new records
function timetable_weekdays_order_autoincrement_filter($data, $postarr)
{
	if(!function_exists("get_current_screen"))
		return $data;
	$screen = get_current_screen();
	if(!is_null($screen) && $screen->action=="add" && $screen->post_type=="timetable_weekdays")
	{
		global $wpdb;
		$menu_order = $wpdb->get_var("SELECT MAX(menu_order)+1 AS menu_order FROM {$wpdb->posts} 
			WHERE 
			post_type='{$screen->post_type}' 
			AND post_status='publish'");
		$menu_order = $menu_order>0 ? $menu_order : 1;
		$data["menu_order"] = $menu_order;
	}
	return $data;
}
add_filter("wp_insert_post_data", "timetable_weekdays_order_autoincrement_filter", "99", 2);
?>