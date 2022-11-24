<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName

namespace Cognate\Tests\HookHandler;

use Cognate\CognateRepo;
use Cognate\CognateStore;
use Cognate\HookHandler\CognatePageHookHandler;
use Content;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\MockObject\MockObject;
use Title;

/**
 * @covers \Cognate\HookHandler\CognatePageHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognatePageHookHandlerTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @var MockObject|CognateStore
	 */
	private $repo;

	protected function setUp(): void {
		parent::setUp();
		$repo = $this->createMock( CognateRepo::class );
		$this->repo = $repo;
		$this->overrideMwServices(
			null,
			[
				'CognateRepo' => static function () use ( $repo ) {
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

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'isNew', 'isRedirect' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[ 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editRedirectToNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', Title::newFromText( 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', Title::newFromText( 'ArticleDbKey' ),
			[]
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
			$revisionRecord = $this->createMock( RevisionRecord::class );
			if ( in_array( 'hasPreviousRevision', $options ) ) {
				$previousContent = $this->createMock( Content::class );
				$previousContent->method( 'isRedirect' )
					->willReturn( in_array( 'wasRedirect', $options ) );

				$previousRevisionRecord = $this->createMock( RevisionRecord::class );
				$previousRevisionRecord->method( 'getContent' )
					->willReturn( $previousContent );
			}
		}

		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->overridePreviousRevision(
			static function ( $revRecord ) use ( $previousRevisionRecord ) {
				return $previousRevisionRecord;
			}
		);
		$handler->onPageContentSaveComplete(
			$linkTarget,
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

		$this->assertSame( [], $updates );
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
			$handler->overrideRevisionNewFromId( static function () {
				return null;
			} );
		} else {
			$handler->overrideRevisionNewFromId( function () use ( $latestRevIsRedirect ) {
				$content = $this->createMock( Content::class );
				$content->method( 'isRedirect' )
					->willReturn( $latestRevIsRedirect );
				$revision = $this->createMock( RevisionRecord::class );
				$revision->method( 'getContent' )
					->willReturn( $content );
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

}
