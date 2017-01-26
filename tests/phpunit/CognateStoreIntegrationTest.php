<?php

namespace Cognate\Tests;

use Cognate\CognateStore;
use Cognate\StringHasher;
use MediaWiki\MediaWikiServices;
use TitleValue;

/**
 * @covers Cognate\CognateStore
 *
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 *
 * @group Database
 */
class CognateStoreIntegrationTest extends \MediaWikiTestCase {

	/** @var CognateStore */
	private $store;

	/** @var StringHasher */
	private $hasher;

	/** @var int */
	private $UTPageNameHash;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed = [
			CognateStore::PAGES_TABLE_NAME,
			CognateStore::TITLES_TABLE_NAME,
			CognateStore::SITES_TABLE_NAME,
		];
		$this->store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
		$this->hasher = new StringHasher();
		$this->UTPageNameHash = $this->hash( 'UTPage' );
	}

	private function hash( $string ) {
		return $this->hasher->hash( $string );
	}

	public function testInsertPageCreatesNewEntry() {
		$success = $this->store->insertPage( 'enwiktionary', new TitleValue( 0, 'My_test_page' ) );

		$this->assertTrue( $success );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[ [ $this->hash( 'enwiktionary' ), $this->hash( 'My_test_page' ), 0 ] ]
		);
	}

	public function testInsertPageWithExistingEntry() {
		$firstSuccess = $this->store->insertPage(
			'enwiktionary',
			new TitleValue( 0, 'My_second_test_page' )
		);
		$secondSuccess = $this->store->insertPage(
			'enwiktionary',
			new TitleValue( 0, 'My_second_test_page' )
		);

		$this->assertTrue( $firstSuccess );
		$this->assertTrue( $secondSuccess );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[ [ $this->hash( 'enwiktionary' ), $this->hash( 'My_second_test_page' ), 0 ] ]
		);
	}

	public function testInsertPageWithExistingEntryOnOtherWiki() {
		$firstSuccess = $this->store->insertPage(
			'enwiktionary',
			new TitleValue( 0, 'My_second_test_page' )
		);
		$secondSuccess = $this->store->insertPage(
			'dewiktionary',
			new TitleValue( 0, 'My_second_test_page' )
		);

		$this->assertTrue( $firstSuccess );
		$this->assertTrue( $secondSuccess );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[
				[ $this->hash( 'dewiktionary' ), $this->hash( 'My_second_test_page' ), 0 ],
				[ $this->hash( 'enwiktionary' ), $this->hash( 'My_second_test_page' ), 0 ],
			]
		);
	}

	public function testSelectLinksForPageReturnsAllInterwikis() {
		$this->setMwGlobals( 'wgDBname', 'enwiktionary' );
		$this->store->insertSites( [
			'enwiktionary' => 'en',
			'dewiktionary' => 'de',
			'eowiktionary' => 'eo',
		] );

		$this->store->insertPage( 'enwiktionary', new TitleValue( 0, 'My_test_page' ) );
		$this->store->insertPage( 'enwiktionary', new TitleValue( 0, 'Another_unrelated_page' ) );
		$this->store->insertPage( 'dewiktionary', new TitleValue( 0, 'My_test_page' ) );
		$this->store->insertPage( 'eowiktionary', new TitleValue( 0, 'My_test_page' ) );

		$interwikis = $this->store->selectLinkDetailsForPage(
			'enwiktionary',
			new TitleValue( 0, 'My_test_page' )
		);

		$this->assertArrayEquals(
			[
				[ 'interwiki' => 'de', 'namespaceID' => 0, 'title' => 'My_test_page' ],
				[ 'interwiki' => 'eo', 'namespaceID' => 0, 'title' => 'My_test_page' ],
			],
			$interwikis );
	}

	public function testInsertAndDeletePageResultsInNoEntry() {
		$this->store->insertPage( 'enwiktionary', new TitleValue( 0, 'My_test_page' ) );
		$this->store->deletePage( 'enwiktionary', new TitleValue( 0, 'My_test_page' ) );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[]
		);
	}

	public function testInsertPages_noPages() {
		$this->store->insertPages( [] );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[]
		);
	}

	public function testInsertPages_onePage() {
		$this->store->insertPages(
			[ [ 'site' => 'enwiktionary', 'namespace' => 0, 'title' => 'Berlin' ] ]
		);

		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[ [ $this->hash( 'enwiktionary' ), $this->hash( 'Berlin' ), '0' ] ]
		);
	}

	public function testInsertPages_multiplePages() {
		$this->store->insertPages( [
			[ 'site' => 'enwiktionary', 'namespace' => 0, 'title' => 'Berlin' ],
			[ 'site' => 'frwiktionary', 'namespace' => 1, 'title' => 'Foo' ],
		] );
		$this->assertSelect(
			'cognate_pages',
			[ 'cgpa_site', 'cgpa_title', 'cgpa_namespace' ],
			[ "cgpa_title != {$this->UTPageNameHash}" ],
			[
				[ $this->hash( 'frwiktionary' ), $this->hash( 'Foo' ), '1' ],
				[ $this->hash( 'enwiktionary' ), $this->hash( 'Berlin' ), '0' ],
			]
		);
	}

	public function testInsertSites_noSites() {
		$this->store->insertSites( [] );
		$this->assertSelect(
			'cognate_sites',
			[ 'cgsi_dbname', 'cgsi_interwiki' ],
			[],
			[]
		);
	}

	public function testInsertSites_oneSite() {
		$this->store->insertSites( [ 'enwiktionary' => 'en' ] );
		$this->assertSelect(
			'cognate_sites',
			[ 'cgsi_dbname', 'cgsi_interwiki' ],
			[],
			[
				[ 'enwiktionary', 'en' ],
			]
		);
	}

}
