<?php
/**
 * Custom Taxonomy Cleanup Unit TestCase
 *
 * @package Custom Taxonomy Cleanup
 */
class CTC_Taxonomy_Cleanup_UnitTestCase extends \WP_UnitTestCase {

	protected $cleanup;

	/**
	 * Set up.
	 */
	function setUp() {
		$this->cleanup = new CTC_Taxonomy_Cleanup();
		$user_id       = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$user          = wp_set_current_user( $user_id );
	}

	/**
	 * Creates terms.
	 *
	 * @param string  $taxonomy Taxonomy name. Default 'ctax'.
	 * @param integer $number   How may terms to create. Defaut 5.
	 * @param bool    $delete   Delete terms before creating terms. Default true.
	 * @return array Array with terms.
	 */
	function create_terms( $taxonomy = 'ctax', $number = 5, $delete = true ) {

		if ( $delete ) {
			$this->_delete_all_data();
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			$this->register_taxonomy( $taxonomy );
		}

		if ( 'category' !== $taxonomy ) {
			// Also create normal category terms for testing.
			$this->factory->term->create_many( $number, array( 'taxonomy' => 'category' ) );
		}

		// Create custom taxonomy terms.
		$t = $this->factory->term->create_many( $number,
			array(
				'taxonomy' => $taxonomy,
			)
		);

		// Return terms.
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );

		return $terms;
	}

	/**
	 * Creates terms for a custom taxonomy and unregisters the taxonomy after.
	 *
	 * @param string  $taxonomy Taxonomy name. Default 'ctax'.
	 * @param integer $number   How may terms to create. Defaut 5.
	 * @param bool    $delete   Delete all terms before creating terms. Default true.
	 */
	function create_not_registered_taxonomy_terms( $taxonomy = 'ctax', $number = 5, $delete = true ) {
		$terms = $this->create_terms( $taxonomy, $number, $delete );
		unregister_taxonomy( $taxonomy );
		return $terms;
	}

	/**
	 * Registers a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name. Default 'ctax'.
	 */
	function register_taxonomy( $taxonomy = 'ctax' ) {
		$args = array( 'hierarchical' => true, 'label' => 'Custom Taxonomy' );
		register_taxonomy( $taxonomy, 'post', $args );
	}

	/**
	 * Mocks the form field values and request globals.
	 *
	 * @param string $taxonomy Taxonomy name. Default 'ctax'.
	 */
	function mock_admin_page_globals( $taxonomy = 'ctax' ) {
		$_REQUEST['security'] = wp_create_nonce( 'custom_taxonomy_cleanup_nonce' );
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['custom_taxonomy_cleanup'] = true;
		$_POST['ctc_taxonomy'] = $taxonomy;
	}

	function _delete_all_data() {
		global $wpdb;

		foreach ( array(
				$wpdb->posts,
				$wpdb->postmeta,
				$wpdb->comments,
				$wpdb->commentmeta,
				$wpdb->term_relationships,
				$wpdb->termmeta
			) as $table ) {
			$wpdb->query( "DELETE FROM {$table}" );
		}

		foreach ( array(
				$wpdb->terms,
				$wpdb->term_taxonomy
			) as $table ) {
			$wpdb->query( "DELETE FROM {$table} WHERE term_id != 1" );
		}

		$wpdb->query( "UPDATE {$wpdb->term_taxonomy} SET count = 0" );

		$wpdb->query( "DELETE FROM {$wpdb->users} WHERE ID != 1" );
		$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE user_id != 1" );
	}

	/**
	 * Returns the output from the plugin admin page
	 *
	 * @return string Plugin admin page HTML.
	 */
	function get_admin_page() {
		ob_start();
		$this->cleanup->admin_page();
		$admin_page = ob_get_clean();
		return $admin_page;
	}

	/**
	 * Sets the batch size
	 *
	 * @param integer $size Number of terms to delete in one batch.
	 */
	function set_batch_size( $size = 5 ) {
		add_filter( 'custom_taxonomy_cleanup_batch_size',
			function( $val ) use ( $size ) {
				return $size;
			}
		);
	}
}
