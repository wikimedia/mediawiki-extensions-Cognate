<?php

namespace Cognate\Tests;

use Cognate\CognatePageHookHandler;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use Content;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\MockObject\MockObject;
use Title;

/**
 * @covers \Cognate\CognatePageHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognatePageHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var MockObject|CognateStore
	 */
	private $repo;

	public function setUp() : void {
		parent::setUp();
		$repo = $this->getMockBuilder( CognateRepo::class )
			->disableOriginalConstructor()
			->getMock();
		$this->repo = $repo;
		$this->overrideMwServices(
			null,
			[
				'CognateRepo' => function () use ( $repo ) {
					return $repo;
				},
			]
		);
	}

	public function test_onPageContentSaveComplete_noNamespaceMatch() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ NS_PROJECT ], 'abc2', Title::newFromText( 'ArticleDbKey' )
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_nullEdit() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'hasNoRevision', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_createNewNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'isNew' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_createNewRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'isNew', 'isRedirect' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'isRedirect', 'wasRedirect', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editNonRedirectToRedirect() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'isRedirect', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editRedirectToNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'wasRedirect', 'hasPreviousRevision' ]
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $dbName
	 * @param LinkTarget $linkTarget
	 * @param string[] $options
	 *        Value in array: isNew, isRedirect, wasRedirect, hasNoRevision, hasPreviousRevision
	 *        Will be false if not included in array
	 */
	private function call_onPageContentSaveComplete(
		array $namespaces,
		$dbName,
		LinkTarget $linkTarget,
		array $options = []
	) {
		$revisionRecord = null;
		$previousRevisionRecord = null;
		if ( !in_array( 'hasNoRevision', $options ) ) {
			$revisionRecord = $this->getMockRevisionRecord();
			if ( in_array( 'hasPreviousRevision', $options ) ) {
				$previousContent = $this->createMock( Content::class );
				$previousContent->expects( $this->any() )
					->method( 'isRedirect' )
					->will( $this->returnValue( in_array( 'wasRedirect', $options ) ) );

				$previousRevisionRecord = $this->getMockRevisionRecord();
				$previousRevisionRecord->expects( $this->any() )
					->method( 'getContent' )
					->will( $this->returnValue( $previousContent ) );
			}
		}

		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->overridePreviousRevision(
			function ( $revRecord ) use ( $previousRevisionRecord ) {
				return $previousRevisionRecord;
			}
		);
		$handler->onPageContentSaveComplete(
			$linkTarget,
			in_array( 'isRedirect', $options ),
			in_array( 'isNew', $options ),
			$revisionRecord
		);
	}

	public function test_onWikiPageDeletionUpdates_namespaceMatch() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$updates = $this->call_onWikiPageDeletionUpdates(
			[ 0 ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' )
		);

		$this->assertCount( 1, $updates );
		$updates[0]->doUpdate();
	}

	public function test_onWikiPageDeletionUpdates_noNamespaceMatch() {
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$updates = $this->call_onWikiPageDeletionUpdates(
			[ NS_PROJECT ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' )
		);

		$this->assertEmpty( $updates );
	}

	/**
	 * @param int[] $namespaces
	 * @param string $dbName
	 * @param LinkTarget $linkTarget
	 *
	 * @return DeferrableUpdate[]
	 */
	private function call_onWikiPageDeletionUpdates(
		array $namespaces,
		$dbName,
		LinkTarget $linkTarget
	) {
		$updates = [];

		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->onWikiPageDeletionUpdates(
			$linkTarget,
			$updates
		);

		return $updates;
	}

	public function test_onArticleUndelete_namespaceMatch_noRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' )
		);
	}

	public function test_onArticleUndelete_namespaceMatch_redirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' ),
			true
		);
	}

	public function test_onArticleUndelete_noNamespaceMatch_noRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ NS_PROJECT ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' )
		);
	}

	public function test_onArticleUndelete_noRevisionForRevisionId() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			Title::newFromText( 'ArticleDbKey' ),
			false,
			true
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $dbName
	 * @param Title $title
	 * @param bool $latestRevIsRedirect
	 * @param bool $latestRevIsNull
	 *
	 */
	private function call_onArticleUndelete(
		array $namespaces,
		$dbName,
		Title $title,
		$latestRevIsRedirect = false,
		$latestRevIsNull = false
	) {
		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		if ( $latestRevIsNull ) {
			$handler->overrideRevisionNewFromId( function () {
				return null;
			} );
		} else {
			$handler->overrideRevisionNewFromId( function () use ( $latestRevIsRedirect ) {
				$content = $this->getMockContent();
				$content->expects( $this->any() )
					->method( 'isRedirect' )
					->will( $this->returnValue( $latestRevIsRedirect ) );
				$revision = $this->createMock( RevisionRecord::class );
				$revision->expects( $this->any() )
					->method( 'getContent' )
					->will( $this->returnValue( $content ) );
				return $revision;
			} );
		}

		$handler->onArticleUndelete(
			$title
		);
	}

	public function test_onTitleMoveComplete_namespaceMatch() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKeyOld' ) );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKeyNew' ) );

		$this->call_onTitleMoveComplete(
			[ 0 ],
			'abc2',
			Title::newFromText( 'ArticleDbKeyOld' ),
			Title::newFromText( 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_noNamespaceMatch() {
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			Title::newFromText( 'ArticleDbKeyOld' ),
			Title::newFromText( 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchOld() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKeyOld', NS_PROJECT ) );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			Title::newFromText( 'ArticleDbKeyOld', NS_PROJECT ),
			Title::newFromText( 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchNew() {
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKeyNew', NS_PROJECT ) );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			Title::newFromText( 'ArticleDbKeyOld' ),
			Title::newFromText( 'ArticleDbKeyNew', NS_PROJECT )
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $dbName
	 * @param LinkTarget $linkTarget
	 * @param LinkTarget $newLinkTarget
	 */
	private function call_onTitleMoveComplete(
		array $namespaces,
		$dbName,
		LinkTarget $linkTarget,
		LinkTarget $newLinkTarget
	) {
		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->onTitleMoveComplete(
			Title::newFromLinkTarget( $linkTarget ),
			Title::newFromLinkTarget( $newLinkTarget )
		);
	}

	/**
	 * @return MockObject|RevisionRecord
	 */
	private function getMockRevisionRecord() {
		return $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return MockObject|Content
	 */
	private function getMockContent() {
		return $this->getMockBuilder( Content::class )
			->disableOriginalConstructor()
			->getMock();
	}

}
