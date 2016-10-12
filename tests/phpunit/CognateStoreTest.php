<?php

use MediaWiki\MediaWikiServices;

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
		$this->tablesUsed = [ CognateStore::TITLES_TABLE_NAME ];
		$this->store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
	}

	public function testSavePageCreatesNewEntry() {
		$this->store->savePage( 'en', new TitleValue( 0, 'My_test_page' ) );
		$this->assertSelect(
			'cognate_titles',
			[ 'cgti_site', 'cgti_title', 'cgti_key', 'cgti_namespace' ],
			[ 'cgti_title != "UTPage"' ],
			[ [ 'en', 'My_test_page',  'My_test_page', 0 ] ]
		);
	}

	public function testSavePageWithExistingEntryIgnoresErrors() {
		$this->store->savePage( 'en', new TitleValue( 0, 'My_second_test_page' ) );
		$this->store->savePage( 'en', new TitleValue( 0, 'My_second_test_page' ) );
		$this->assertSelect(
			'cognate_titles',
			[ 'cgti_site', 'cgti_title', 'cgti_key', 'cgti_namespace' ],
			[ 'cgti_title != "UTPage"' ],
			[ [ 'en', 'My_second_test_page',  'My_second_test_page', 0 ] ]
		);
	}

	public function testGetTranslationsForPageReturnsAllLanguages() {
		$this->store->savePage( 'en', new TitleValue( 0, 'My_test_page' ) );
		$this->store->savePage( 'en', new TitleValue( 0, 'Another_unrelated_page' ) );
		$this->store->savePage( 'de', new TitleValue( 0, 'My_test_page' ) );
		$this->store->savePage( 'eo', new TitleValue( 0, 'My_test_page' ) );
		$languages = $this->store->getLinksForPage( 'en', new TitleValue( 0, 'My_test_page' ) );
		$this->assertArrayEquals( [ 'de', 'eo' ], $languages );
	}

	public function testSaveAndDeletePageResultsInNoEntry() {
		$this->store->savePage( 'en', new TitleValue( 0, 'My_test_page' ) );
		$this->store->deletePage( 'en', new TitleValue( 0, 'My_test_page' ) );
		$this->assertSelect(
			'cognate_titles',
			[ 'cgti_site', 'cgti_title', 'cgti_key', 'cgti_namespace' ],
			[ 'cgti_title != "UTPage"' ],
			[]
		);
	}

}
