<?php

/**
 * Plugin Name:     WPGraphQL for YITH WooCommerce Product Add-Ons
 * Plugin URI:      https://github.com/
 * Description:     Adds YITH WooCommerce Product Add-Ons to the WPGraphQL Schema
 * Author:          Built By Todd
 * Author URI:      
 * Text Domain:     
 * Domain Path:     /languages
 * Version:         0.0.1
 */

namespace ConsumeDesign\YithAddOns;

//namespace ConsumeDesign;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Initialize the plugin
 */
function init()
{
	/**
	 * If WPGraphQL is not active, show the admin notice
	 */
	if (!class_exists('WPGraphQL') && !class_exists('WP_GraphQL_WooCommerce')) {
		add_action('admin_init', __NAMESPACE__ . '\show_admin_notice');
	} else {
		add_filter('graphql_register_types', __NAMESPACE__ . '\register_yith_types');
	}
}

add_action('init', __NAMESPACE__ . '\init');

/**
 * Show admin notice to admins if this plugin is active but either ACF and/or WPGraphQL
 * are not active
 *
 * @return bool
 */
function show_admin_notice()
{
	/**
	 * For users with lower capabilities, don't show the notice
	 */
	if (!current_user_can('manage_options')) {
		return false;
	}

	add_action(
		'admin_notices',
		function () {
?>
		<div class="error notice">
			<p><?php _e(
					'WPGraphQL must be active for "wp-graphql-yith-add-ons" to work',
					'consume'
				); ?></p>
		</div>
<?php
		}
	);

	return true;
}

function register_yith_types()
{
	register_graphql_object_type('keyValue', [
		'description' => __('Keys and their values, both cast as strings', 'your-textdomain'),
		'fields'      => [
			'form_type' => [
				'type' => 'String',
			],
			'image'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'label'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'description'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'placeholder'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'tooltip'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'price'   => [
				'type' => ['list_of' => 'Strings'],
			],
			'type'   => [
				'type' => ['list_of' => 'Strings'],
			],
		]
	]);

	register_graphql_field('Product', 'yith_fields', [
		'type'        => ['list_of' => 'KeyValue'],
		'description' => __('Field that resolves as a list of keys and values', 'your-textdomain'),
		'resolve'     => function () {
			global $wpdb;
			global $post;
			$product_id = $post->ID;
			$sql = "SELECT " . $wpdb->prefix . "yith_wapo_types.type, " . $wpdb->prefix . "yith_wapo_types.options FROM " . $wpdb->prefix . "yith_wapo_groups JOIN " . $wpdb->prefix . "yith_wapo_types on " . $wpdb->prefix . "yith_wapo_groups.id = " . $wpdb->prefix . "yith_wapo_types.group_id WHERE FIND_IN_SET($product_id, products_id)";
			$results = $wpdb->get_results($sql);
			if ($results) {
				$array = array();
				foreach ($results as $result) {
					$type = array('form_type' => $result->type);
					$options =  maybe_unserialize($result->options);
					$result = array_merge($type, $options);
					$array[] = $result;
				}
				$fields = !empty($array) ? $array : null;
			} else {
				$fields = null;
			}
			//graphql_debug($product_id, ['type' => 'product_id']);
			//graphql_debug($wpdb->prefix, ['type' => 'prefix']);
			return $fields;
		}
	]);
}
