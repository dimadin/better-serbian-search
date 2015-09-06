<?php

/**
 * The Better Serbian Search Plugin
 *
 * Search all variants of word for Serbian language.
 *
 * @package Better_Serbian_Search
 * @subpackage Main
 */

/**
 * Plugin Name: Better Serbian Search
 * Plugin URI:  http://blog.milandinic.com/wordpress/plugins/
 * Description: Search all variants of word for Serbian language.
 * Author:      Milan Dinić
 * Author URI:  http://blog.milandinic.com/
 * Version:     0.3
 * Text Domain: better-serbian-search
 * Domain Path: /languages/
 * License:     GPL
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

// Load dependencies
require __DIR__ . '/vendor/autoload.php';

/**
 * Search all variants of word for Serbian language. 
 *
 * @since 1.0
 */
class Better_Serbian_Search {
	/**
	 * Add starting method to appropriate hook.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function __construct() {
		// Start our process as late as possible
		add_action( 'wp_loaded', array( $this, 'start' ), 1, 999 );

		// Register plugins action links filter
		add_filter( 'plugin_action_links',               array( $this, 'action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'action_links' ), 10, 2 );
	}

	/**
	 * Add action links to plugins page.
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array  $links       Existing plugin's action links.
	 * @param string $plugin_file Path to the plugin file.
	 * @return array $links New plugin's action links.
	 */
	public function action_links( $links, $plugin_file ) {
		// Set basename
		$basename = plugin_basename( __FILE__ );

		// Check if it is for this plugin
		if ( $basename != $plugin_file ) {
			return $links;
		}

		// Load translations
		load_plugin_textdomain( 'better-serbian-search', false, dirname( $basename ) . '/languages' );

		// Add new link
		$links['donate']   = '<a href="http://blog.milandinic.com/donate/">' . __( 'Donate', 'better-serbian-search' ) . '</a>';
		$links['wpdev']    = '<a href="http://blog.milandinic.com/wordpress/custom-development/">' . __( 'WordPress Developer', 'better-serbian-search' ) . '</a>';
		$links['premiums'] = '<strong><a href="https://shop.milandinic.com/">' . __( 'Premium WordPress Plugins', 'better-serbian-search' ) . '</a></strong>';

		return $links;
	}

	/**
	 * Start process of getting all Serbian variants for search.
	 *
	 * Loads Serbian_Variants class, search parser and register end process.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function start() {
		// Only proceed if there is search term
		if ( ! isset( $_GET['s'] ) || ! $_GET['s'] ) {
			return;
		}

		// Start Serbian_Variants class
		$this->variants_object = new Serbian_Variants( $_GET['s'] );

		// Stringify new search terms
		$search_terms = implode( ' ', $this->variants_object->variants );

		// Set new search terms as one used
		$_GET['s'] = $search_terms;

		// Register end of process and search parser as early as possible
		add_action( 'template_redirect', array( $this, 'end'          ), 1, 1 );
		add_filter( 'posts_search',      array( $this, 'parse_search' ), 1, 2 );
	}

	/**
	 * End process of getting all Serbian variants for search.
	 *
	 * Use old search term back to global variable.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function end() {
		// Get old search term
		$search_term = $this->variants_object->original_term;

		// Set back old search term as one used in global var
		$_GET['s'] = $search_term;

		// Set back old search term as one used in query var
		set_query_var( 's', $search_term );

		// Deregister search parser
		remove_filter( 'posts_search', array( $this, 'parse_search' ), 1, 2 );
	}

	/**
	 * Generate SQL for the WHERE clause based on passed search terms.
	 *
	 * Based on WP_Query::parse_search().
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @global wpdb $wpdb
	 * @return string WHERE clause.
	 */
	public function parse_search( $search, $object ) {
		global $wpdb;

		// Query class
		$better_serbian_search_query = new Better_Serbian_Search_Query;

		// Shorthand.
		$q = &$object->query_vars;

		$search = '';

		// added slashes screw with quote grouping when done early, so done later
		$q['s'] = stripslashes( $q['s'] );
		if ( empty( $_GET['s'] ) && $object->is_main_query() )
			$q['s'] = urldecode( $q['s'] );
		// there are no line breaks in <input /> fields
		$q['s'] = str_replace( array( "\r", "\n" ), '', $q['s'] );
		$q['search_terms_count'] = 1;
		if ( ! empty( $q['sentence'] ) ) {
			$q['search_terms'] = array( $q['s'] );
		} else {
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['s'], $matches ) ) {
				$q['search_terms_count'] = count( $matches[0] );
				$q['search_terms'] = $better_serbian_search_query->parse_search_terms_wrapper( $matches[0] );
			} else {
				$q['search_terms'] = array( $q['s'] );
			}
		}

		$n = ! empty( $q['exact'] ) ? '' : '%';
		$searchand = '';
		$q['search_orderby_title'] = array();
		foreach ( $q['search_terms'] as $term ) {
			if ( $n ) {
				$like = '%' . $wpdb->esc_like( $term ) . '%';
				$q['search_orderby_title'][] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $like );
			}

			$like = $n . $wpdb->esc_like( $term ) . $n;
			$search .= $wpdb->prepare( "{$searchand}(($wpdb->posts.post_title LIKE %s) OR ($wpdb->posts.post_content LIKE %s))", $like, $like );
			$searchand = ' OR ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() )
				$search .= " AND ($wpdb->posts.post_password = '') ";
		}

		return $search;
	}
}

/**
 * Initialize Better_Serbian_Search.
 *
 * Load class when all plugins are loaded
 * so that other plugins can overwrite it.
 *
 * @since 1.0
 */
function better_serbian_search_instantiate() {
	global $better_serbian_search;
	$better_serbian_search = new Better_Serbian_Search();
}
add_action( 'plugins_loaded', 'better_serbian_search_instantiate', 15 );
