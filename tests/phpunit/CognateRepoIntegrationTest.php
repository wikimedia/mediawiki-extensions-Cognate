<?php

namespace Cognate\Tests;

use Cognate\CognateRepo;
use Cognate\CognateStore;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use TitleValue;

/**
 * @covers Cognate\CognateRepo
 *
 * @license GNU GPL v2+
 * @author Addshore
 *
 * @group Database
 */
class CognateRepoIntegrationTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		$this->setMwGlobals(
			[
				'wgCognateDb' => false,
				'wgCognateCluster' => false,
				'wgCognateGroup' => 'GROUPNAME',
			]
		);
		$this->tablesUsed[] = CognateStore::TITLES_TABLE_NAME;
	}

	/**
	 * @return CognateRepo
	 */
	private function getCognateRepo() {
		return MediaWikiServices::getInstance()->getService( 'CognateRepo' );
	}

	public function testSavePage() {
		$repo = $this->getCognateRepo();
		$linkTarget = new TitleValue( 0, 'Foo' );
		$languageCode = 'de';

		$this->assertTrue( $repo->savePage( $languageCode, $linkTarget ) );

		$this->assertEquals(
			[],
			$repo->getLinksForPage( $languageCode, $linkTarget )
		);
		$this->assertEquals(
			[ $languageCode ],
			$repo->getLinksForPage( 'OtherSite', $linkTarget )
		);
	}

	public function testSaveDeleteRoundTrip() {
		$repo = $this->getCognateRepo();
		$linkTarget = new TitleValue( 0, 'Foo2' );
		$languageCode = 'de';

		$this->assertTrue( $repo->savePage( $languageCode, $linkTarget ) );
		$this->assertTrue( $repo->deletePage( $languageCode, $linkTarget ) );

		$this->assertEquals(
			[],
			$repo->getLinksForPage( $languageCode, $linkTarget )
		);
		$this->assertEquals(
			[],
			$repo->getLinksForPage( 'OtherSite', $linkTarget )
		);
	}

}