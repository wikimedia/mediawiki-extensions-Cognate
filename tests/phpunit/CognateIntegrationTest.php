<?php

namespace Cognate\Tests;

use Cognate\CognateRepo;
use Cognate\CognateStore;
use DeferredUpdates;
use MediaWiki\Linker\LinkTarget;
use MediaWikiIntegrationTestCase;
use PageArchive;
use Title;
use TitleValue;
use User;

/**
 * @covers \Cognate\CognateHooks
 * @covers \Cognate\HookHandler\CognatePageHookHandler
 * @covers \Cognate\CognateRepo
 * @covers \Cognate\CognateStore
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 *
 * @group Database
 */
class CognateIntegrationTest extends MediaWikiIntegrationTestCase {

	use CheckSystemReqsTrait;

	/** @var string */
	private $pageName;
	/** @var string */
	private $dbName;

	protected function setUp(): void {
		parent::setUp();

		$this->markTestSkippedIfNo64bit();

		$services = $this->getServiceContainer();
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
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromID( $pageDetails['id'] );

		$page->doDeleteArticleReal( __METHOD__, $this->getTestSysop()->getUser() );

		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $pageDetails['title'] );
	}

	/**
	 * @depends testCreateDeleteAndRestorePageResultsInEntry
	 */
	public function testCreateAndMovePageResultsInCorrectEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$services = $this->getServiceContainer();
		$page = $services->getWikiPageFactory()->newFromID( $pageDetails['id'] );
		$oldTitle = $page->getTitle();
		$newTitle = Title::newFromText( $oldTitle->getDBkey() . '-new' );

		$movePage = $services->getMovePageFactory()->newMovePage( $oldTitle, $newTitle );
		$movePage->move( User::newFromId( 0 ), 'reason', true );
		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $oldTitle );
		$this->assertTitle( $newTitle );
	}

	public function testCreateDeleteAndRestorePageResultsInEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromID( $pageDetails['id'] );
		$title = $page->getTitle();

		$page->doDeleteArticleReal( __METHOD__, $this->getTestSysop()->getUser() );

		DeferredUpdates::doUpdates();
		$archive = new PageArchive( $title );
		// Warning! If the "move" test above is executed first, this undeletes a redirect!
		$archive->undeleteAsUser( [], $this->getTestSysop()->getUser() );

		$this->assertTitle( $title );
	}

	/**
	 * @return CognateRepo
	 */
	private function getRepo() {
		return $this->getServiceContainer()->getService( 'CognateRepo' );
	}

	/**
	 * @param LinkTarget $target
	 */
	private function assertTitle( LinkTarget $target ) {
		$this->assertSame(
			[ str_replace( '_', ' ', $this->dbName ) . '-prefix:' . $target->getDBkey() ],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

	/**
	 * @param LinkTarget $target
	 */
	private function assertNoTitle( LinkTarget $target ) {
		$this->assertSame(
			[],
			$this->getRepo()->getLinksForPage( 'xxx', $target )
		);
	}

}
