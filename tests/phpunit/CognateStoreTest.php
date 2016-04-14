<?php

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @group Database
 */
class CognateStoreTest extends MediaWikiTestCase {

	/** @var CognateStore */
	private $interlanguage;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed = [ 'inter_language_titles' ];
		$this->interlanguage = new CognateStore( wfGetLB(), false );
		// TODO Find a way to handle the page 'UTPage' that gets added by MediaWikiTestCase::addCoreDBData
		// UTPage interferes with the first test
	}

	public function testSavePageCreatesNewEntry() {
		$this->interlanguage->savePage( 'en', 'My_test_page' );
		$this->assertSelect(
			'inter_language_titles',
			[ 'ilt_language', 'ilt_title' ],
			[ ],
			[ [ 'en', 'My_test_page' ] ]
		);
	}

	public function testSavePageWithExistingEntryIgnoresErrors() {
		$this->interlanguage->savePage( 'en', 'My_second_test_page' );
		$this->interlanguage->savePage( 'en', 'My_second_test_page' );
		$this->assertSelect(
			'inter_language_titles',
			[ 'ilt_language', 'ilt_title' ],
			[ ],
			[ [ 'en', 'My_second_test_page' ] ]
		);
	}

	public function testGetTranslationsForPageReturnsAllLanguages() {
		$this->interlanguage->savePage( 'en', 'My_test_page' );
		$this->interlanguage->savePage( 'en', 'Another_unrelated_page' );
		$this->interlanguage->savePage( 'de', 'My_test_page' );
		$this->interlanguage->savePage( 'eo', 'My_test_page' );
		$languages = $this->interlanguage->getTranslationsForPage( 'en', 'My_test_page' );
		$this->assertArrayEquals( [ 'de', 'eo' ], $languages );
	}

}
