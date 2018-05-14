<?php

namespace Cognate\Tests;

use Cognate\CognatePageHookHandler;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use Content;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use PHPUnit_Framework_MockObject_MockObject;
use Revision;
use Title;
use TitleValue;
use User;
use WikiPage;

/**
 * @covers Cognate\CognatePageHookHandler
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognatePageHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject|CognateStore
	 */
	private $repo;

	public function setUp() {
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
			[ NS_PROJECT ], 'abc2', new TitleValue( 0, 'ArticleDbKey' )
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_nullEdit() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'hasNoRevision', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_createNewNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'isNew' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_createNewRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'isNew', 'isRedirect' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editExistingRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'isRedirect', 'wasRedirect', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editNonRedirectToRedirect() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
			[ 'isRedirect', 'hasPreviousRevision' ]
		);
	}

	public function test_onPageContentSaveComplete_namespaceMatch_editRedirectToNonRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );

		$this->call_onPageContentSaveComplete(
			[ 0 ], 'abc2', new TitleValue( 0, 'ArticleDbKey' ),
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
		$options = []
	) {
		/** @var WikiPage|PHPUnit_Framework_MockObject_MockObject $mockWikiPage */
		$mockWikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();
		$mockWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromLinkTarget( $linkTarget ) ) );

		$content = $this->getMock( Content::class );
		$content->expects( $this->any() )
			->method( 'isRedirect' )
			->will( $this->returnValue( in_array( 'isRedirect', $options ) ) );

		$revision = null;
		if ( !in_array( 'hasNoRevision', $options ) ) {
			$revision = $this->getMockRevision();

			$previousRevision = null;
			if ( in_array( 'hasPreviousRevision', $options ) ) {
				$previousContent = $this->getMock( Content::class );
				$previousContent->expects( $this->any() )
					->method( 'isRedirect' )
					->will( $this->returnValue( in_array( 'wasRedirect', $options ) ) );

				$previousRevision = $this->getMockRevision();
				$previousRevision->expects( $this->any() )
					->method( 'getContent' )
					->will( $this->returnValue( $previousContent ) );
			}

			$revision->expects( $this->any() )
				->method( 'getPrevious' )
				->will( $this->returnValue( $previousRevision ) );
		}

		$flags = 0;
		if ( in_array( 'isNew', $options ) ) {
			$flags = EDIT_NEW;
		}

		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->onPageContentSaveComplete(
			$mockWikiPage,
			User::newFromId( 0 ),
			$content,
			null, null, null, null,
			$flags,
			$revision,
			$this->getMock( 'Status' ),
			null
		);
	}

	public function test_onWikiPageDeletionUpdates_namespaceMatch() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );
		$this->repo->expects( $this->never() )
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
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$updates = $this->call_onWikiPageDeletionUpdates(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
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

		/** @var WikiPage|PHPUnit_Framework_MockObject_MockObject $mockWikiPage */
		$mockWikiPage = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();
		$mockWikiPage->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromLinkTarget( $linkTarget ) ) );

		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->onWikiPageDeletionUpdates(
			$mockWikiPage,
			null,
			$updates
		);

		return $updates;
	}

	public function test_onArticleUndelete_namespaceMatch_noRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->once() )
			->method( 'savePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKey' ) );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);
	}

	public function test_onArticleUndelete_namespaceMatch_redirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ 0 ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' ),
			true
		);
	}

	public function test_onArticleUndelete_noNamespaceMatch_noRedirect() {
		$this->repo->expects( $this->never() )->method( 'deletePage' );
		$this->repo->expects( $this->never() )->method( 'savePage' );

		$this->call_onArticleUndelete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKey' )
		);
	}

	/**
	 * @param int[] $namespaces
	 * @param string $dbName
	 * @param LinkTarget $linkTarget
	 * @param bool $latestRevIsRedirect
	 */
	private function call_onArticleUndelete(
		array $namespaces,
		$dbName,
		LinkTarget $linkTarget,
		$latestRevIsRedirect = false
	) {
		$handler = new CognatePageHookHandler( $namespaces, $dbName );
		$handler->overrideRevisionNewFromId( function () use ( $latestRevIsRedirect ) {
			$content = $this->getMockContent();
			$content->expects( $this->any() )
				->method( 'isRedirect' )
				->will( $this->returnValue( $latestRevIsRedirect ) );
			$revision = $this->getMockRevision();
			$revision->expects( $this->any() )
				->method( 'getContent' )
				->will( $this->returnValue( $content ) );
			return $revision;
		} );
		$handler->onArticleUndelete(
			$this->getMockTitle( $linkTarget ),
			null, null, null
		);
	}

	public function test_onTitleMoveComplete_namespaceMatch() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( 0, 'ArticleDbKeyOld' ) );
		$this->repo->expects( $this->once() )
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
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( 0, 'ArticleDbKeyOld' ),
			new TitleValue( 0, 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchOld() {
		$this->repo->expects( $this->once() )
			->method( 'deletePage' )
			->with( 'abc2', new TitleValue( NS_PROJECT, 'ArticleDbKeyOld' ) );
		$this->repo->expects( $this->never() )
			->method( 'savePage' );

		$this->call_onTitleMoveComplete(
			[ NS_PROJECT ],
			'abc2',
			new TitleValue( NS_PROJECT, 'ArticleDbKeyOld' ),
			new TitleValue( 0, 'ArticleDbKeyNew' )
		);
	}

	public function test_onTitleMoveComplete_namespaceMatchNew() {
		$this->repo->expects( $this->never() )
			->method( 'deletePage' );
		$this->repo->expects( $this->once() )
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
		return $this->getMockBuilder( Revision::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|Content
	 */
	private function getMockContent() {
		return $this->getMockBuilder( Content::class )
			->disableOriginalConstructor()
			->getMock();
	}

	/**
	 * @param LinkTarget $value
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|Title
	 */
	private function getMockTitle( LinkTarget $value ) {
		$mock = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		foreach ( get_class_methods( $value ) as $methodName ) {
			if ( strstr( $methodName, '__' ) ) {
				continue;
			}
			$mock->expects( $this->any() )
				->method( $methodName )
				->will( $this->returnCallback( function () use ( $value, $methodName ) {
					return call_user_func_array( [ $value, $methodName ], func_get_args() );
				} ) );
		}
		$mock->expects( $this->any() )
			->method( 'getTitleValue' )
			->will( $this->returnValue( $value ) );
		$mock->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 5566778899 ) );
		return $mock;
	}

}
