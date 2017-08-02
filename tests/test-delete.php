<?php
/**
 * Test case for deleting terms.
 */
class CTC_Test_Delete extends CTC_Taxonomy_Cleanup_UnitTestCase {

	/**
	 * Set up.
	 */
	function setUp() {
		parent::setUp();
		$this->set_batch_size( 5 );
	}

	/**
	 * Test deleting terms in batches.
	 */
	function test_admin_page_deleting_terms_in_batches() {
		$terms = $this->create_terms( 'ctax', 10 );
		$this->assertEquals( 10, count( get_terms( 'taxonomy=ctax&hide_empty=0' ) ) );
		$this->mock_admin_page_globals();
		$this->cleanup->register_taxonomy();
		$admin_page = $this->get_admin_page();
		$terms_remaining = get_terms( 'taxonomy=ctax&hide_empty=0' );
		$this->assertEquals( 5, count( $terms_remaining ) );
	}

	/**
	 * Test deleting terms in batches without incorrectly deleting built taxonomies.
	 *
	 * @depends test_admin_page_deleting_terms_in_batches
	 */
	function test_admin_page_deleting_terms_in_batches_from_correct_taxonomy() {
		$this->create_not_registered_taxonomy_terms( 'cpt', 5 );
		$this->mock_admin_page_globals();
		$this->cleanup->register_taxonomy();
		$admin_page = $this->get_admin_page();
		$this->assertEquals( 0, count( get_terms( 'taxonomy=ctax&hide_empty=0' ) ) );
		$admin_page = $this->get_admin_page();

		// 6 terms is created terms + default category (Uncategorized)
		$this->assertEquals( 6, count( get_terms( 'taxonomy=category&hide_empty=0' ) ) );
	}
}
