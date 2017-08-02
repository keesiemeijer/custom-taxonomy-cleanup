<?php
/**
 * Test case for taxonomies.
 */
class CTC_Test_Taxonomies extends CTC_Taxonomy_Cleanup_UnitTestCase {

	/**
	 * Set up.
	 */
	function setUp() {
		parent::setUp();
		$this->set_batch_size( 5 );
		$this->_delete_all_data();
	}

	/**
	 * Test default taxonomy types found in database.
	 */
	function test_database_taxonomies_default() {
		$taxonomies = $this->cleanup->db_taxonomies();
		// Default category Uncategorized should exist in the database.
		$this->assertEquals( array( 'category' ), $taxonomies );
	}

	/**
	 * Test all database taxonomies
	 */
	function test_database_taxonomies() {
		$this->create_not_registered_taxonomy_terms();
		$taxonomies = $this->cleanup->db_taxonomies();
		$expected   = array( 'category', 'ctax' );
		sort( $expected );
		sort( $taxonomies );
		$this->assertEquals( $expected, $taxonomies );
	}

	/**
	 * Test if an unused taxonomy is found.
	 *
	 * @depends test_database_taxonomies
	 */
	function test_unused_taxonomy_found() {
		$terms = $this->create_not_registered_taxonomy_terms();
		$this->assertEquals( array( 'ctax' ), $this->cleanup->get_unused_taxonomies() );
	}

	/**
	 * Test if an unused taxonomy is re-registered.
	 *
	 * @depends test_unused_taxonomy_found
	 */
	function test_unused_taxonomy_is_registered() {
		$this->create_not_registered_taxonomy_terms();
		$this->mock_admin_page_globals( 'ctax' );
		$this->cleanup->register_taxonomy();
		$this->assertTrue( taxonomy_exists( 'ctax' ) );
	}
}
