<?php
class CTC_Taxonomy_Cleanup {


	private $batch_size = 100;
	private $unused_tax;

	/**
	 * Initialize plugin
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {

		if ( ! is_admin() ) {
			return;
		}

		// Add the admin page for this plugin.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Adds a settings page for this plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_menu() {
		$page_hook = add_management_page(
			__( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ),
			__( 'Custom Taxonomy Cleanup', 'custom-taxonomy-cleanup' ),
			'manage_options',
			'custom-taxonomy-cleanup.php',
			array( $this, 'admin_page' )
		);

		add_action( 'load-' . $page_hook, array( $this, 'register_taxonomy' ) );
		add_action( 'admin_print_scripts-' . $page_hook, array( $this, 'enqueue_script' ) );
	}


	/**
	 * Enqueue Javascript for confirm dialog to delte terms
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_script() {

		wp_register_script( 'custom-taxonomy-cleanup', plugins_url( 'js/custom-taxonomy-cleanup.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_enqueue_script( 'custom-taxonomy-cleanup' );

		/* translators: %s: taxonomy name */
		$confirm = __( 'You are about to permanently delete terms from the taxonomy: %s.', 'custom-taxonomy-cleanup' );
		$confirm .= "\n  " . __( "'Cancel' to stop, 'OK' to delete.", 'custom-taxonomy-cleanup' );
		$js_vars = array(
			'confirm' => $confirm,
			'remove_storage' => (bool) ! ( 'POST' === $_SERVER['REQUEST_METHOD'] ),
		);

		wp_localize_script( 'custom-taxonomy-cleanup', 'ctc_plugin', $js_vars );
	}

	/**
	 * Displays the admin settings page for this plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_page() {
		$taxonomy     = '';
		$notice        = '';
		$options       = '';
		$plugin_url    = admin_url( 'plugins.php#custom-taxonomy-cleanup' );
		$total         = 0;

		$plugin_text   = _x(
			'Custom Taxonomy Cleanup',
			'Text of link to plugin',
			'custom-taxonomy-cleanup'
		);

		$plugin_link = '<a href="https://github.com/keesiemeijer/custom-taxonomy-cleanup">' . $plugin_text . '</a>';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'custom_taxonomy_cleanup_nonce', 'security' );
			$taxonomy = $this->get_requested_taxonomy();
			$notice   = $this->delete_terms( stripslashes_deep( $_POST ) );
		}

		$unused_tax = is_array( $this->unused_tax ) ? $this->unused_tax : array();
		$type_count = count( $unused_tax );
		$type_str   = _n( 'custom taxonomy', 'custom taxonomies', $type_count );

		if ( ! empty( $unused_tax ) ) {
			foreach ( $unused_tax as $unused_type ) {
				$selected = ( $unused_type === $taxonomy ) ? " selected='selected'" : '';
				$value = esc_attr( $unused_type );
				$count = $this->get_term_count( $unused_type );
				$total += $count;
				$count = $count ? ' (' . $count . ')' :  '';
				$options .= "<option value='{$value}'{$selected}>{$value}{$count}</option>";
			}

			require plugin_dir_path( __FILE__ ) . 'templates/admin-form.php';
		} else {
			require plugin_dir_path( __FILE__ ) . 'templates/admin-no-terms-found.php';
		}
	}

	/**
	 * Re-registers an unused taxonomy if needed.
	 *
	 * @since  1.1.0
	 */
	public function register_taxonomy() {
		$this->unused_tax = $this->get_unused_taxonomies();
		$taxonomy         = $this->get_requested_taxonomy();

		/**
		 * Filter the batch size.
		 *
		 * @since  1.1.0
		 * @param int $batch_size Batch size. Default 100.
		 */
		$batch_size = apply_filters( 'custom_taxonomy_cleanup_batch_size', $this->batch_size, $taxonomy );
		$this->batch_size = absint( $batch_size );

		if ( ! empty( $this->unused_tax ) && ! empty( $taxonomy ) ) {

			// Register the non-existent taxonomy.
			// This way we can use wp_delete_term() to delete the terms.

			register_taxonomy( $taxonomy, array( 'post' ),
				array(
					'public'       => false,
					'hierarchical' => true,
				)
			);
		}
	}

	/**
	 * Get taxonomies no longer in use.
	 *
	 * @since  1.1.0
	 * @return array Array with unused taxonomy names.
	 */
	public function get_unused_taxonomies() {
		$unused_tax    = array();
		$taxonomies    = array_keys( get_taxonomies() );
		$db_taxonomies = $this->db_taxonomies();

		if ( ! empty( $db_taxonomies ) ) {
			$unused_tax = array_diff( $db_taxonomies, $taxonomies );
		}

		return array_values( $unused_tax );
	}


	/**
	 * Returns taxonomy from a $_POST request.
	 *
	 * @since 1.0.0
	 * @return string Taxonomy to delete terms for or empty string.
	 */
	private function get_requested_taxonomy() {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return '';
		}

		$request = stripslashes_deep( $_POST );

		// Check it's this plugin's settings form that was submitted.
		if ( ! isset( $request['custom_taxonomy_cleanup'] ) ) {
			return '';
		}

		return isset( $request['ctc_taxonomy'] ) ? $request['ctc_taxonomy'] : '';
	}

	/**
	 * Delete terms from the database.
	 *
	 * @since 1.0.0
	 * @param unknown $args array $_POST request with taxonomy to delete terms from.
	 * @return string Admin notices.
	 */
	private function delete_terms( $args ) {
		global $wpdb;

		$msg       = '';
		$taxonomy  = isset( $args['ctc_taxonomy'] ) ? $args['ctc_taxonomy'] : '';

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			$msg = __( 'Error: invalid taxonomy', 'custom-taxonomy-cleanup' );
			return '<div class="error"><p>' . $msg . '</p></div>';
		}

		// Get term ids for this taxonomy in batches.
		$db_term_ids = $this->get_term_ids( $taxonomy, $this->batch_size );

		if ( empty( $db_term_ids ) ) {
			/* translators: %s: taxonomy name */
			$no_terms_msg = __( 'Notice: No terms found for the taxonomy: %s', 'custom-taxonomy-cleanup' );
			$msg = sprintf( $no_terms_msg, $taxonomy );
			return '<div class="notice"><p>' . $msg . '</p></div>';
		}

		$deleted = 0;
		foreach ( $db_term_ids as $term_id ) {
			$del = wp_delete_term( $term_id, $taxonomy );
			if ( false !== $del ) {
				++$deleted;
			}
		}

		if ( $deleted ) {
			/* translators: 1: deleted term count, 2: taxonomy name  */
			$updated = _n(
				'Deleted %1$d term from the taxonomy: %2$s',
				'Deleted %1$d terms from the taxonomy: %2$s',
				$deleted,
				'custom-taxonomy-cleanup'
			);

			$updated = sprintf( $updated, $deleted, $taxonomy );
			$msg = '<div class="updated"><p>' . $updated . '</p></div>';
		}

		// Check if there more terms from this taxonomy to delete.
		$db_term_ids = $this->get_term_ids( $taxonomy );

		if ( ! empty( $db_term_ids ) ) {
			$count = count( $db_term_ids );

			/* translators: 1: term count, 2: taxonomy name  */
			$notice = _n(
				'Still %1$d term left in the database from the taxonomy: %2$s',
				'Still %1$d terms left in the database from the taxonomy: %2$s',
				$count,
				'custom-taxonomy-cleanup'
			);

			$notice = sprintf( $notice , $count, $taxonomy );

			$msg .= '<div class="notice"><p>' . $notice . '</p></div>';
		} else {
			/* No more terms from this taxonomy left in the database. */

			$key = array_search( $taxonomy, $this->unused_tax );
			if ( false !== $key ) {
				unset( $this->unused_tax[ $key ] );

				/* translators: %s: taxonomy name */
				$notice = __( 'No more terms left in the database from the taxonomy: %s', 'custom-taxonomy-cleanup' );
				$notice = sprintf( $notice, $taxonomy );
				$msg .= '<div class="notice"><p>' . $notice . '</p></div>';
			}
		}

		return $msg;
	}

	/**
	 * Returns taxonomies in the database.
	 *
	 * @since 1.0.0
	 * @return array Array with taxonomies in the database.
	 */
	public function db_taxonomies() {
		global $wpdb;
		$query = "SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy";
		return $wpdb->get_col( $query );
	}


	/**
	 * Returns the term count for a taxonomy.
	 *
	 * @since 1.0.0
	 * @param string $taxonomy Taxonomy.
	 * @return integer Term count for a taxonomy.
	 */
	public function get_term_count( $taxonomy ) {
		global $wpdb;
		$query = "SELECT COUNT( t.term_id ) FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN (%s)";
		return $wpdb->get_var( $wpdb->prepare( $query, $taxonomy ) );
	}


	/**
	 * Returns term ids from a taxonomy.
	 *
	 * @since 1.0.0
	 * @param string  $taxonomy Taxonomy.
	 * @param integer $limit    Limit how many ids are returned. Default 100.
	 * @return array Array with term ids.
	 */
	public function get_term_ids( $taxonomy, $limit = 100 ) {
		global $wpdb;

		if ( ! absint( $limit ) ) {
			return array();
		}

		$query = "SELECT t.term_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN (%s) LIMIT %d";

		return $wpdb->get_col( $wpdb->prepare( $query, $taxonomy, absint( $limit ) ) );
	}
}
