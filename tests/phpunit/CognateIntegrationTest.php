<?php

namespace Cognate\Tests;

use Cognate\CognateRepo;
use Cognate\CognateStore;
use DeferredUpdates;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use MovePage;
use PageArchive;
use Title;
use TitleValue;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @author Addshore
 *
 * @group Database
 */
class CognateIntegrationTest extends MediaWikiTestCase {

	private $pageName;
	private $dbName;

	public function setUp() {
		parent::setUp();

		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$this->pageName = 'CognateIntegrationTest-pageName';
		$this->dbName = $config->get( 'DBname' );
		$this->tablesUsed[] = CognateStore::TITLES_TABLE_NAME;
		$this->tablesUsed[] = CognateStore::PAGES_TABLE_NAME;
		$this->tablesUsed[] = CognateStore::SITES_TABLE_NAME;

		// Insert the current site to our sites table
		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );
		$store->insertSites( [ $this->dbName => $this->dbName . '-prefix' ] );
	}

	public function testNoPagesByDefault() {
		$this->assertNoTitle( new TitleValue( 0, $this->pageName ) );
	}

	public function testCreatePageCreatesCognateEntry() {
		$pageDetails = $this->insertPage( $this->pageName );

		$this->assertTitle( $pageDetails['title'] );
	}

	public function testCreateAndDeletePageResultsInNoEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$page = WikiPage::newFromID( $pageDetails['id'] );

		$page->doDeleteArticle( __METHOD__ );
		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $pageDetails['title'] );
	}

	public function testCreateAndMovePageResultsInCorrectEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$page = WikiPage::newFromID( $pageDetails['id'] );
		$oldTitle = $page->getTitle();
		$newTitle = Title::newFromText( $oldTitle->getDBkey() . '-new' );

		$movePage = new MovePage( $oldTitle, $newTitle );
		$movePage->move( User::newFromId( 0 ), 'reason', true );
		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $oldTitle );
		$this->assertTitle( $newTitle );
	}

	public function testCreateDeleteAndRestorePageResultsInEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$page = WikiPage::newFromID( $pageDetails['id'] );
		$title = $page->getTitle();

		$page->doDeleteArticle( __METHOD__ );
		DeferredUpdates::doUpdates();
		$archive = new PageArchive( $title );
		$archive->undelete( [] );

		$this->assertTitle( $title );
	}

	/**
	 * @return CognateRepo
	 */
	private function getRepo() {
		return MediaWikiServices::getInstance()->getService( 'CognateRepo' );
	}

	private function assertTitle( LinkTarget $target ) {
		$this->assertEquals(
			[ str_replace( '_', ' ', $this->dbName ) . '-prefix:' . $target->getDBkey() ],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

	private function assertNoTitle( LinkTarget $target ) {
		$this->assertEquals(
			[],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

}
