<?php

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @group Database
 */
class CognateStoreTest extends MediaWikiTestCase {

	/** @var CognateStore */
	private $store;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed = [ 'inter_language_titles' ];
		$this->store = new CognateStore( wfGetLB(), false );
	}

	public function testSavePageCreatesNewEntry() {
		$this->store->savePage( 'en', 'My_test_page' );
		$this->assertSelect(
			'inter_language_titles',
			[ 'ilt_language', 'ilt_title' ],
			[ 'ilt_title != "UTPage"' ],
			[ [ 'en', 'My_test_page' ] ]
		);
	}

	public function testSavePageWithExistingEntryIgnoresErrors() {
		$this->store->savePage( 'en', 'My_second_test_page' );
		$this->store->savePage( 'en', 'My_second_test_page' );
		$this->assertSelect(
			'inter_language_titles',
			[ 'ilt_language', 'ilt_title' ],
			[ 'ilt_title != "UTPage"' ],
			[ [ 'en', 'My_second_test_page' ] ]
		);
	}

	public function testGetTranslationsForPageReturnsAllLanguages() {
		$this->store->savePage( 'en', 'My_test_page' );
		$this->store->savePage( 'en', 'Another_unrelated_page' );
		$this->store->savePage( 'de', 'My_test_page' );
		$this->store->savePage( 'eo', 'My_test_page' );
		$languages = $this->store->getTranslationsForPage( 'en', 'My_test_page' );
		$this->assertArrayEquals( [ 'de', 'eo' ], $languages );
	}

	public function testSaveAndDeletePageResultsInNoEntry() {
		$this->store->savePage( 'en', 'My_test_page' );
		$this->store->deletePage( 'en', 'My_test_page' );
		$this->assertSelect(
			'inter_language_titles',
			[ 'ilt_language', 'ilt_title' ],
			[ 'ilt_title != "UTPage"' ],
			[]
		);
	}

}
