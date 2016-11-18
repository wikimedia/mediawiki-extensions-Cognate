<?php

namespace Cognate\Tests;

use Cognate\CognateRepo;
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

	public function setUp() {
		parent::setUp();
		$this->pageName = __CLASS__ . '-pageName';
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
		global $wgLanguageCode;
		$this->assertEquals(
			[ $wgLanguageCode ],
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