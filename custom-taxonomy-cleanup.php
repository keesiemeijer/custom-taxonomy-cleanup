<?php
/*
Plugin Name: Custom Taxonomy Cleanup
Version: 1.0
Plugin URI:
Description: Detect and delete terms from custom taxonomies that are no longer in use.
Author: keesiemijer
Author URI:
License: GPL v2+
Text Domain: custom-taxonomy-cleanup
Domain Path: /languages

Custom Taxonomy Cleanup
Copyright 2015  Kees Meijer  (email : keesie.meijer@gmail.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version. You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Custom_Taxonomy_Cleanup {

	private $taxonomies;
	private $db_taxonomies;
	private $tax_options;

	public function __construct() {

		load_plugin_textdomain( 'custom-taxonomy-cleanup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Hook in *very* late to catch all registered custom taxonomies. ¯\_(ツ)_/¯
		add_action( 'init', array( $this, 'init' ), 9999999 );
	}


	/**
	 * Initialize plugin
	 *
	 * @since 1.0
	 * @return void
	 */
	public function init() {

		// Registered taxonomies in global $wp_taxonomies variable.
		$this->taxonomies = array_keys( get_taxonomies() );

		// Taxonomies found in the database.
		$this->db_taxonomies = $this->db_taxonomies();

		// Unregistered (unused) taxonomies.
		$this->tax_options = array();

		// Taxonomy from $_POST request (to delete terms from).
		$taxonomy = $this->get_requested_taxonomy();

		if ( !empty( $this->db_taxonomies ) ) {
			$this->tax_options = array_diff( $this->db_taxonomies, $this->taxonomies );
		}

		if ( !empty( $this->tax_options ) && !empty( $taxonomy ) ) {

			// Register the non-existent taxonomy.
			// This way we can use wp_delete_term() to delete the terms.

			// Set hierachical to false as there is no need to re-assign child terms.
			register_taxonomy( $taxonomy, array( 'post' ),
				array(
					'public' => false,
					'hierarchical' => false,
				)
			);
		}

		// Add the admin page for this plugin.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}


	/**
	 * Adds a settings page for this plugin.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function admin_menu() {
		$page_hook = add_management_page(
			__( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ),
			__( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ),
			'manage_options',
			'custom-taxonomy-cleanup.php',
			array( $this, 'admin_page' ) );

		add_action( 'admin_print_scripts-' . $page_hook, array( $this, 'enqueue_script' ) );
	}


	/**
	 * Enqueue Javascript for confirm dialog to delte terms
	 *
	 * @since 1.0
	 * @return void
	 */
	public function enqueue_script() {

		wp_register_script( 'custom-taxonomy-cleanup', plugins_url( '/custom-taxonomy-cleanup.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_enqueue_script( 'custom-taxonomy-cleanup' );


		$js_vars = array(
			/* translators: %s: taxonomy name */
			'confirm' => __( 'You are about to permanently delete terms from the taxonomy: %s.', 'custom-taxonomy-cleanup' ),
			'remove_storage' => (bool)  !( 'POST' === $_SERVER['REQUEST_METHOD'] ),
		);

		$js_vars['confirm'] .= "\n  " . __( "'Cancel' to stop, 'OK' to delete.", 'custom-taxonomy-cleanup' );

		wp_localize_script( 'custom-taxonomy-cleanup', 'ctc_plugin', $js_vars );
	}


	/**
	 * Returns taxonomies in the database.
	 *
	 * @since 1.0
	 * @return array Array with taxonomies in the database.
	 */
	private function db_taxonomies() {
		global $wpdb;
		$query = "SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy";
		return $wpdb->get_col( $query );
	}


	/**
	 * Returns taxonomy from a $_POST request.
	 *
	 * @since 1.0
	 * @return string Taxonomy to delete terms for or empty string.
	 */
	private function get_requested_taxonomy() {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return '';
		}

		$request = stripslashes_deep( $_POST );

		// Check it's this plugin's settings form that was submitted
		if ( !isset( $request['custom_taxonomy_cleanup'] ) ) {
			return '';
		}

		return isset( $request['ctc_taxonomy'] ) ? $request['ctc_taxonomy'] : '';
	}


	/**
	 * Displays the admin settings page for this plugin.
	 *
	 * @since 1.0
	 * @return void
	 */
	public function admin_page() {

		$header        = __( 'Delete terms from custom taxonomies that are currently not registered (no longer in use).', 'custom-taxonomy-cleanup' );
		$taxonomy      = '';
		$delete_notice = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'custom_taxonomy_cleanup_nonce' );

			$taxonomy      = $this->get_requested_taxonomy();
			$delete_notice = $this->delete_terms( stripslashes_deep( $_POST ) );
		}

		// Start admin page output
		echo '<div class="wrap rpbt_cache">';
		echo '<h1>' . __( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ) . '</h1>';

		echo $delete_notice;

		if ( !empty( $this->tax_options ) ) {
			// Unused taxonomy terms found.

			$unregistered = _n( 'Terms from unused taxonomy detected!', 'Terms from unused taxonomies detected!', count( $this->tax_options ), 'custom-taxonomy-cleanup' );

			echo '<h3 style="color:Chocolate;">' . $unregistered . '</h3>';
			echo '<p>' . $header . '<br/>' . __( 'Terms are deleted in batches of 500 terms.', 'custom-taxonomy-cleanup' ) . '</p>';
			echo '<p>' . __( "It's recommended you <strong style='color:red;'>make a database backup</strong> before proceeding.", 'custom-taxonomy-cleanup' ) . '</p>';

			echo '<form method="post" action="">';
			wp_nonce_field( 'custom_taxonomy_cleanup_nonce' );

			echo "<table class='form-table'>";
			$label = '<label for="ctc_taxonomy">' . __( 'Custom Taxonomy', 'custom-taxonomy-cleanup' ) . '</label>';
			echo "<tr><th scope='row'>{$label}</th>";
			echo '<td><select id="ctc_taxonomy" name="ctc_taxonomy">';

			foreach ( $this->tax_options as $tax_option ) {
				$selected = ( $tax_option === $taxonomy ) ? " selected='selected'" : '';
				$value = esc_attr( $tax_option );
				echo "<option value='{$value}'{$selected}>{$value}</option>";
			}

			echo '</select>';
			echo '<p class="description">' . __( 'The taxonomy you want to delete terms from.', 'custom-taxonomy-cleanup' ) . '</p>';
			echo '</td></tr></table>';
			submit_button( __( 'Delete Terms!', 'custom-taxonomy-cleanup' ), 'primary', 'custom_taxonomy_cleanup' );
			echo '</form>';

		} else {
			// No unused taxonomy terms found.

			if ( empty( $delete_notice ) ) {
				echo '<p>' . $header . '</p>';
			}

			$plugin_url    = admin_url( 'plugins.php#custom-taxonomy-cleanup' );
			$tax_notice    = __( 'No unused custom taxonomies found!', 'custom-taxonomy-cleanup' );
			$tax_notice    = $tax_notice . ' ' . "<a href='{$plugin_url}'>" . __( 'De-activate this plugin', 'custom-taxonomy-cleanup' ) . '</a>';
			$tax_notice    = '<div class="updated"><p>' . $tax_notice . '</p></div>';

			echo $tax_notice;
		}

		echo '</div>';
	}


	/**
	 * Delete terms from the database.
	 *
	 * @since 1.0
	 * @param $args array $_POST request with taxonomy to delete terms from.
	 * @return string Admin notices.
	 */
	private function delete_terms( $args ) {
		global $wpdb;

		$msg = '';
		$taxonomy = isset( $args['ctc_taxonomy'] ) ? $args['ctc_taxonomy'] : '';

		if ( empty( $taxonomy ) ) {
			$msg .= '<div class="error"><p>' . __( 'Error: invalid taxonomy', 'custom-taxonomy-cleanup' ) . '</p></div>';
			return $msg;
		}

		$query = "SELECT t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN (%s)";
		$db_terms = $wpdb->get_col( $wpdb->prepare( $query . " LIMIT 50", $taxonomy ) );

		if ( empty( $db_terms ) ) {
			/* translators: %s: taxonomy name */
			$msg .= '<div class="notice"><p>' . sprintf( __( 'Notice: No terms found for the taxonomy: %s', 'custom-taxonomy-cleanup' ), $taxonomy ) . '</p></div>';
			return $msg;
		}

		$deleted = 0;
		foreach ( $db_terms as $term ) {
			$del = wp_delete_term( $term, $taxonomy );
			if ( true === $del ) {
				++$deleted;
			}
		}

		if ( $deleted ) {
			/* translators: 1: deleted term count, 2: taxonomy name  */
			$msg .= '<div class="updated"><p>' . sprintf( __( 'Deleted %1$d terms from the taxonomy: %2$s ', 'custom-taxonomy-cleanup' ), $deleted, $taxonomy ) . '</p></div>';
		}

		// Check if there more terms from this taxonomy to delete.
		$db_terms = $wpdb->get_col( $wpdb->prepare( $query, $taxonomy ) );

		if ( !empty( $db_terms ) ) {
			/* translators: 1: term count, 2: taxonomy name  */
			$msg .= '<div class="notice"><p>' . sprintf( __( 'Still %1$d terms left in the database from the taxonomy: %2$s ', 'custom-taxonomy-cleanup' ), count( $db_terms ), $taxonomy ) . '</p></div>';
		} else {
			// No more terms from this taxonomy in the database.

			if ( ( $key = array_search( $taxonomy, $this->tax_options ) ) !== false ) {
				unset( $this->tax_options[ $key ] );

				/* translators: %s: taxonomy name */
				$msg .= '<div class="updated"><p>' . sprintf( __( 'No more terms left in the database from the taxonomy: %s ', 'custom-taxonomy-cleanup' ), $taxonomy ) . '</p></div>';
			}
		}

		return $msg;
	}
}

$custom_taxonomy_cleanup = new Custom_Taxonomy_Cleanup();