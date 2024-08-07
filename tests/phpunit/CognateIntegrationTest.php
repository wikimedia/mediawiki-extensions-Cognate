<?php

namespace Cognate\Tests;

use Cognate\CognateRepo;
use Cognate\CognateStore;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWikiIntegrationTestCase;

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

		$this->deletePage( $page, __METHOD__ );

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
		$movePage->move( $this->getTestUser()->getUserIdentity(), 'reason', true );
		DeferredUpdates::doUpdates();

		$this->assertNoTitle( $oldTitle );
		$this->assertTitle( $newTitle );
	}

	public function testCreateDeleteAndRestorePageResultsInEntry() {
		$pageDetails = $this->insertPage( $this->pageName );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromID( $pageDetails['id'] );
		$title = $page->getTitle();

		$this->deletePage( $page, __METHOD__ );

		DeferredUpdates::doUpdates();
		// Warning! If the "move" test above is executed first, this undeletes a redirect!
		$this->getServiceContainer()->getUndeletePageFactory()
			->newUndeletePage( $page, $this->getTestSysop()->getAuthority() )
			->undeleteUnsafe( '' );

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
