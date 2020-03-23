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
 * @covers \Cognate\CognateHooks
 * @covers \Cognate\CognatePageHookHandler
 * @covers \Cognate\CognateRepo
 * @covers \Cognate\CognateStore
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 *
 * @group Database
 */
class CognateIntegrationTest extends MediaWikiTestCase {

	use CheckSystemReqsTrait;

	private $pageName;
	private $dbName;

	public function setUp() : void {
		parent::setUp();

		$this->markTestSkippedIfNo64bit();

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

		if ( version_compare( MW_VERSION, '1.35', '<' ) ) {
			$page->doDeleteArticle( __METHOD__ );
		} else {
			$page->doDeleteArticleReal( __METHOD__, $this->getTestSysop()->getUser() );
		}

		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $pageDetails['title'] );
	}

	/**
	 * @depends testCreateDeleteAndRestorePageResultsInEntry
	 */
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

		if ( version_compare( MW_VERSION, '1.35', '<' ) ) {
			$page->doDeleteArticle( __METHOD__ );
		} else {
			$page->doDeleteArticleReal( __METHOD__, $this->getTestSysop()->getUser() );
		}

		DeferredUpdates::doUpdates();
		$archive = new PageArchive( $title );
		// Warning! If the "move" test above is executed first, this undeletes a redirect!
		if ( method_exists( $archive, 'undeleteAsUser' ) ) {
			$archive->undeleteAsUser( [], $this->getTestSysop()->getUser() );
		} else {
			$archive->undelete( [] );
		}

		$this->assertTitle( $title );
	}

	/**
	 * @return CognateRepo
	 */
	private function getRepo() {
		return MediaWikiServices::getInstance()->getService( 'CognateRepo' );
	}

	private function assertTitle( LinkTarget $target ) {
		$this->assertSame(
			[ str_replace( '_', ' ', $this->dbName ) . '-prefix:' . $target->getDBkey() ],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

	private function assertNoTitle( LinkTarget $target ) {
		$this->assertSame(
			[],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

}
