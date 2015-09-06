<?php

/**
 * The Better Serbian Search Plugin
 *
 * Search all variants of word for Serbian language.
 *
 * @package Simple_Email_Queue
 * @subpackage Query
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Better_Serbian_Search_Query' ) && class_exists( 'WP_Query' ) ) :
/**
 * Put email in queue and send it one by one, by limits.
 *
 * @since 1.0
 */
class Better_Serbian_Search_Query extends WP_Query {
	/**
	 * Constructor.
	 *
	 * Empty to prevent parent constructor from firing.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query URL query string or array of vars.
	 */
	public function __construct( $query = '' ) {}

	/**
	 * Check if the terms are suitable for searching.
	 *
	 * Wrapper method for WP_Query::parse_search_terms()
	 * to make it public.
	 *
	 * @since 1.0.0
	 *
	 * @param array $terms Terms to check.
	 * @return array Terms that are not stopwords.
	 */
	public function parse_search_terms_wrapper( $terms ) {
		return $this->parse_search_terms( $terms );
	}
}
endif;
