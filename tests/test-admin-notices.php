<?php
/**
 * Test case for admin notices after deleting terms in batches.
 */
class CTC_Test_Admin_Notices extends CTC_Taxonomy_Cleanup_UnitTestCase {

	protected $cleanup;

	/**
	 * Set up.
	 */
	function setUp() {
		parent::setUp();
		$this->set_batch_size( 5 );
		$this->_delete_all_data();
	}

	/**
	 * Test data provider
	 */
	public function batch_provider() {
		return array(
			// Single form, 1 taxonomy.
			array(
				array( 'ctax' => 2 ),
				1,
				array(
					'1 term from 1 unused custom taxonomy detected',
					'Deleted 1 term from the taxonomy: ctax',
					'Still 1 term left in the database from the taxonomy: ctax',
				),
			),
			// Plural form, 1 taxonomy.
			array(
				array( 'ctax' => 5 ),
				2,
				array(
					'3 terms from 1 unused custom taxonomy detected',
					'Deleted 2 terms from the taxonomy: ctax',
					'Still 3 terms left in the database from the taxonomy: ctax',
				),
			),
			// Single form, multiple taxonomies
			array(
				array( 'ctax' => 2, 'ctax2' => 2 ),
				1,
				array(
					'3 terms from 2 unused custom taxonomies detected',
					'Deleted 1 term from the taxonomy: ctax',
					'Still 1 term left in the database from the taxonomy: ctax',
				),
			),
			// Plural form, multiple taxonomies.
			array(
				array( 'ctax' => 5, 'ctax2' => 2 ),
				2,
				array(
					'5 terms from 2 unused custom taxonomies detected',
					'Deleted 2 terms from the taxonomy: ctax',
					'Still 3 terms left in the database from the taxonomy: ctax',
				),
			),
			// No terms found.
			array( array( 'ctax' => 5 ), 5, 'There are no unused custom taxonomy terms found' ),
			array( array( 'ctax' => 5, 'ctax2' => 2 ), 5, 'No more terms left in the database from the taxonomy: ctax' ),
			array( array(), 5, 'Notice: No terms found for the taxonomy: ctax' ),
		);
	}

	/**
	 * Test notices when deleting terms in batches.
	 *
	 * @dataProvider batch_provider
	 * @param array        $taxonomies Array with taxonomy names.
	 * @param int          $batch      Batch size.
	 * @param array|string $notices    Admin notices.
	 */
	function test_batch_admin_notices( $taxonomies, $batch, $notices ) {
		$i = 0;
		foreach ( $taxonomies as $taxonomy => $count ) {
			$count = ! $i ? $count + $batch : $count;
			$this->create_not_registered_taxonomy_terms( $taxonomy, $count, false );
			++$i;
		}

		$this->set_batch_size( $batch );
		$this->mock_admin_page_globals();
		$this->cleanup->register_taxonomy();

		// Deletes the first taxonomy terms to the term type count in the provider.
		$admin_page = $this->get_admin_page();

		// Deletes terms in a batch.
		$admin_page = $this->get_admin_page();

		if ( is_array( $notices ) ) {
			foreach ( $notices as $notice ) {
				$this->assertContains( $notice, $admin_page );
			}
		} else {
			$this->assertContains( $notices, $admin_page );
		}
	}

	/**
	 * Test invalid taxonomy in $_POST request. (should never happen)
	 */
	function test_batch_error_invalid_taxonomy() {
		$this->mock_admin_page_globals( 'invalid_taxonomy' );
		$this->cleanup->register_taxonomy();
		$admin_page = $this->get_admin_page();
		$this->assertContains( 'Error: invalid taxonomy', $admin_page );
	}

	/**
	 * Test admin page without submitting form and no unused taxonomies found.
	 */
	function test_admin_page_without_submitting_form_and_no_unused_taxonomies() {
		$_SERVER['REQUEST_METHOD'] = '';
		$admin_page = $this->get_admin_page();
		$this->assertNotContains( 'Error:', $admin_page );
		$this->assertNotContains( 'Notice:', $admin_page );
	}
}
