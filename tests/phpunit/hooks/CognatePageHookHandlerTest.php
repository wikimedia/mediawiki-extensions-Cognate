<?php

namespace Cognate\Tests;

use Cognate\CognatePageHookHandler;
use Cognate\CognateStore;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use PHPUnit_Framework_MockObject_MockObject;
use Revision;
use Title;
use TitleValue;
use User;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class CognatePageHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|CognateStore
	 */
	private $store;

	public function setUp() {
		parent::setUp();
		$store = $this->getMockBuilder( CognateStore::class )
			->disableOriginalConstructor()
			->getMock();
		$this->store = $store;
		$this->overrideMwServices(
			null,
			[
				'CognateStore' => function () use ( $store ) {
					return $store;
				},
			]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete( [ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );
	}

	public function test_onPageContentSaveComplete_noNamespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onPageContentSaveComplete( [ NS_PROJECT ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );
	}

	/**
	 * @param int[] $namespaces
	 * @param string $language
	 * @param LinkTarget $linkTarget
	 */
	private function call_onPageContentSaveComplete(
		array $namespaces,
		$language,
		LinkTarget $linkTarget
	) {
		/** @var WikiPage|PHPUnit_Framework_MockObject_MockObject $mockWikiPage */
		$mockWikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();
		$mockWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( Title::newFromLinkTarget( $linkTarget ) );

		$handler = new CognatePageHookHandler( $namespaces, $language );
		$handler->onPageContentSaveComplete(
			$mockWikiPage,
			User::newFromId( 0 ),
			$this->getMock( 'Content' ),
			null, null, null, null, null, null,
			$this->getMock( 'Status' ),
			null
		);
	}

	public function test_onWikiPageDeletionUpdates_namespaceMatch() {
		$this->store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$updates = $this->call_onWikiPageDeletionUpdates(
			[ 0 ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);

		$this->assertCount( 1, $updates );
		$updates[0]->doUpdate();
	}

	public function test_onWikiPageDeletionUpdates_noNamespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$updates = $this->call_onWikiPageDeletionUpdates(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);

		$this->assertCount( 0, $updates );
	}

	/**
	 * @param int[] $namespaces
	 * @param string $language
	 * @param LinkTarget $linkTarget
	 *
	 * @return DeferrableUpdate[]
	 */
	private function call_onWikiPageDeletionUpdates(
		array $namespaces,
		$language,
		LinkTarget $linkTarget
	) {
		$updates = [];

		/** @var WikiPage|PHPUnit_Framework_MockObject_MockObject $mockWikiPage */
		$mockWikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();
		$mockWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( Title::newFromLinkTarget( $linkTarget ) );

		$handler = new CognatePageHookHandler( $namespaces, $language );
		$handler->onWikiPageDeletionUpdates(
			$mockWikiPage,
			null,
			$updates
		);

		return $updates;
	}

	public function test_onArticleUndelete_namespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);
	}

	public function test_onArticleUndelete_noNamespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $language
	 * @param LinkTarget $linkTarget
	 */
	private function call_onArticleUndelete(
		array $namespaces,
		$language,
		LinkTarget $linkTarget
	) {
		$handler = new CognatePageHookHandler( $namespaces, $language );
		$handler->onArticleUndelete(
			Title::newFromLinkTarget( $linkTarget ),
			null, null, null
		);
	}

	public function test_onTitleMoveComplete_namespaceMatch() {
		$this->store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKeyOld' ) );
		$this->store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKeyNew' ) );

		$this->call_onTitleMoveComplete(
			[ 0 ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKeyOld' ),
			new TitleValue( 0, 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_noNamespaceMatch() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKeyOld' ),
			new TitleValue( 0, 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchOld() {
		$this->store->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( NS_PROJECT, 'ArticleDbKeyOld' ) );
		$this->store->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( NS_PROJECT, 'ArticleDbKeyOld' ),
			new TitleValue( 0, 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchNew() {
		$this->store->expects( $this->never() )
			->method( 'deletePage' );
		$this->store->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( NS_PROJECT, 'ArticleDbKeyNew' ) );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKeyOld' ),
			new TitleValue( NS_PROJECT, 'ArticleDbKeyNew' )
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $language
	 * @param LinkTarget $linkTarget
	 */
	private function call_onTitleMoveComplete(
		array $namespaces,
		$language,
		LinkTarget $linkTarget,
		LinkTarget $newLinkTarget
	) {
		$handler = new CognatePageHookHandler( $namespaces, $language );
		$handler->onTitleMoveComplete(
			Title::newFromLinkTarget( $linkTarget ),
			Title::newFromLinkTarget( $newLinkTarget ),
			User::newFromId( 0 ),
			null,
			null,
			null,
			$this->getMockRevision()
		);
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|Revision
	 */
	private function getMockRevision() {
		return $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();
	}

}
