<?php
/*
Plugin Name: Dashboard Sticky Notes
Plugin URI: http://www.cmswp.jp/plugins/dashboard_sticky_notes/
Description: This plugin adds the functionality to add sticky notes into the dashboard.
Author: Hiroaki Miyashita
Version: 1.0.2
Author URI: http://www.cmswp.jp/
Text Domain: dashboard-sticky-notes
Domain Path: /
*/

/*  Copyright 2014 Hiroaki Miyashita

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class dashboard_sticky_notes {

	function dashboard_sticky_notes() {
		add_action( 'init', array(&$this, 'dashboard_sticky_notes_init') );
		add_action( 'wp_dashboard_setup', array(&$this, 'dashboard_sticky_notes_wp_dashboard_setup') );
		add_action( 'add_meta_boxes', array(&$this, 'dashboard_sticky_notes_add_meta_boxes') );
		add_action( 'save_post', array(&$this, 'dashboard_sticky_notes_save_post'), 100 );
	}
	
	function dashboard_sticky_notes_init() {
		if ( function_exists('load_plugin_textdomain') ) :
			if ( !defined('WP_PLUGIN_DIR') ) 
				load_plugin_textdomain('dashboard-sticky-notes', str_replace( ABSPATH, '', dirname(__FILE__) ) );
			else
				load_plugin_textdomain('dashboard-sticky-notes', false, dirname( plugin_basename(__FILE__) ) );
		endif;

		$labels = array(
			'name'               => __( 'Sticky Notes', 'dashboard-sticky-notes' ),
			'singular_name'      => __( 'Sticky Note', 'dashboard-sticky-notes' ),
			'menu_name'          => __( 'Sticky Notes', 'dashboard-sticky-notes' ),
			'name_admin_bar'     => __( 'Sticky Note', 'dashboard-sticky-notes' ),
			'add_new'            => __( 'Add New', 'dashboard-sticky-notes' ),
			'add_new_item'       => __( 'Add New Sticky Note', 'dashboard-sticky-notes' ),
			'new_item'           => __( 'New Sticky Note', 'dashboard-sticky-notes' ),
			'edit_item'          => __( 'Edit Sticky Note', 'dashboard-sticky-notes' ),
			'view_item'          => __( 'View Sticky Note', 'dashboard-sticky-notes' ),
			'all_items'          => __( 'All Sticky Notes', 'dashboard-sticky-notes' ),
			'search_items'       => __( 'Search Sticky Notes', 'dashboard-sticky-notes' ),
			'not_found'          => __( 'No Sticky Notes found.', 'dashboard-sticky-notes' ),
			'not_found_in_trash' => __( 'No Sticky Notes found in Trash.', 'dashboard-sticky-notes' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 100,
			'menu_icon'          => 'dashicons-welcome-write-blog',
			'supports'           => array( 'title', 'editor', 'author' )
		);
		register_post_type( 'dsn', $args );		
	}
	
	function dashboard_sticky_notes_wp_dashboard_setup() {
		global $current_user;
		$posts = get_posts( array( 'post_type' => 'dsn' ) );

		if ( !empty($posts) && is_array($posts) ) :
			foreach ( $posts as $key => $post ) :
				$dsn_context  = get_post_meta($post->ID, 'dsn_context', true);
				$dsn_priority = get_post_meta($post->ID, 'dsn_priority', true);
				$dsn_target   = get_post_meta($post->ID, 'dsn_target', true);
			
				if ( in_array($current_user->roles[0], $dsn_target) ) :
					add_meta_box( 'dsn-'.$post->ID, $post->post_title, array(&$this, 'dashboard_sticky_notes_post_content'), 'dashboard', $dsn_context, $dsn_priority, $post );
				endif;
			endforeach;
		endif;
	}
	
	function dashboard_sticky_notes_post_content( $var, $args ) {
		echo do_shortcode(wpautop($args['args']->post_content));
	}
	
	function dashboard_sticky_notes_add_meta_boxes() {
		add_meta_box('dsndiv', __('Output Option', 'dashboard-sticky-notes'), array(&$this, 'dashboard_sticky_notes_add_meta_box'), 'dsn', 'side', 'core');		
	}
	
	function dashboard_sticky_notes_add_meta_box() {
		global $post;

		$dsn_context  = get_post_meta($post->ID, 'dsn_context', true);
		$dsn_priority = get_post_meta($post->ID, 'dsn_priority', true);
		$dsn_target   = get_post_meta($post->ID, 'dsn_target', true);
		if ( empty($dsn_target) ) $dsn_target = array('administrator');
?>
<p><label for="dsn_context"><?php _e('Context', 'dashboard-sticky-notes'); ?></label>
<select name="dsn_context" id="dsn_context">
<option value="normal"<?php selected('normal', $dsn_context); ?>><?php _e('Normal', 'dashboard-sticky-notes'); ?></option>
<option value="side"<?php selected('side', $dsn_context); ?>><?php _e('Side', 'dashboard-sticky-notes'); ?></option>
</select></p>

<p><label for="dsn_priority"><?php _e('Priority', 'dashboard-sticky-notes'); ?></label>
<select name="dsn_priority" id="dsn_priority">
<option value="high"<?php selected('high', $dsn_priority); ?>><?php _e('High', 'dashboard-sticky-notes'); ?></option>
<option value="low"<?php selected('low', $dsn_priority); ?>><?php _e('Low', 'dashboard-sticky-notes'); ?></option>
</select></p>


<p><label for="dsn_target"><?php _e('Target', 'dashboard-sticky-notes'); ?></label>
<select name="dsn_target[]" id="dsn_target" multiple="multiple">
<?php
	$editable_roles = array_reverse( get_editable_roles() );

	foreach ( $editable_roles as $role => $details ) :
		$name = translate_user_role($details['name'] );
		if ( in_array($role, $dsn_target) ) 
			echo "\n\t".'<option selected="selected" value="' . esc_attr($role) . '">' . $name . '</option>';
		else
			echo "\n\t".'<option value="' . esc_attr($role) . '">' . $name . '</option>';
	endforeach;
?>
</select></p>
<?php
	}
	
	function dashboard_sticky_notes_save_post() {
		global $post, $post_id;
		
		if ( !isset($post->post_type) || $post->post_type != 'dsn' ) return;
				
		$dsn_context  = isset($_POST['dsn_context']) ? $_POST['dsn_context'] : null;
		$dsn_priority = isset($_POST['dsn_priority']) ? $_POST['dsn_priority'] : null;
		$dsn_target   = isset($_POST['dsn_target']) ? $_POST['dsn_target'] : null;

		if ( !empty($dsn_context) ) update_post_meta( $post_id, 'dsn_context', $dsn_context );
		if ( !empty($dsn_priority) ) update_post_meta( $post_id, 'dsn_priority', $dsn_priority );
		if ( !empty($dsn_target) ) update_post_meta( $post_id, 'dsn_target', $dsn_target );
	}
}
$dashboard_sticky_notes = new dashboard_sticky_notes();
?>