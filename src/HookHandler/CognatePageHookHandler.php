<?php

namespace Cognate\HookHandler;

use Cognate\CognateRepo;
use Cognate\CognateServices;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MWCallableUpdate;
use MWException;
use Title;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognatePageHookHandler {

	/**
	 * @var string
	 */
	private $dbName;

	/**
	 * @var int[]
	 */
	private $namespaces;

	/**
	 * @var callable
	 */
	private $newRevisionFromIdCallable;

	/**
	 * @var callable
	 */
	private $previousRevisionCallable;

	/**
	 * @param int[] $namespaces array of namespace ids the hooks should operate on
	 * @param string $dbName The dbName of the current site
	 */
	public function __construct( array $namespaces, $dbName ) {
		$this->namespaces = $namespaces;
		$this->dbName = $dbName;
		$this->newRevisionFromIdCallable = static function ( $id ) {
			return MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getRevisionById( $id );
		};
		$this->previousRevisionCallable = static function ( $revRecord ) {
			return MediaWikiServices::getInstance()
				->getRevisionLookup()
				->getPreviousRevision( $revRecord );
		};
	}

	/**
	 * Overrides the use of RevisionLookup::getRevisionById in this class
	 * This is intended for use while testing and will fail if MW_PHPUNIT_TEST is not defined.
	 *
	 * @param callable $callback
	 * @throws MWException
	 */
	public function overrideRevisionNewFromId( callable $callback ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new MWException(
				'Cannot override RevisionLookup::getRevisionById callback in operation.'
			);
		}
		$this->newRevisionFromIdCallable = $callback;
	}

	/**
	 * Overrides the use of RevisionLookup::getPreviousRevision in this class
	 * This is intended for use while testing and will fail if MW_PHPUNIT_TEST is not defined.
	 *
	 * @param callable $callback
	 * @throws MWException
	 */
	public function overridePreviousRevision( callable $callback ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new MWException(
				'Cannot override RevisionLookup::getPreviousRevision callback in operation.'
			);
		}
		$this->previousRevisionCallable = $callback;
	}

	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @note for mediawiki 1.35+, this is run for the PageSaveComplete hook instead of the
	 * PageContentSaveComplete hook
	 *
	 * @param LinkTarget $title
	 * @param RevisionRecord|null $revisionRecord
	 */
	public function onPageContentSaveComplete(
		LinkTarget $title,
		RevisionRecord $revisionRecord = null
	) {
		// A null revision means a null edit / no-op edit was made, no need to process that.
		if ( $revisionRecord === null ) {
			return;
		}

		if ( !$this->isActionableTarget( $title ) ) {
			return;
		}

		$this->onContentChange( $title );
	}

	/**
	 * Manipulate the list of DataUpdates to be applied when a page is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikiPageDeletionUpdates
	 *
	 * @param LinkTarget $title
	 * @param DeferrableUpdate[] &$updates
	 */
	public function onWikiPageDeletionUpdates(
		LinkTarget $title,
		array &$updates
	) {
		if ( $this->isActionableTarget( $title ) ) {
			$updates[] = $this->newDeferrableDelete( $title, $this->dbName );
		}
	}

	/**
	 * @param LinkTarget $linkTarget
	 * @param string $dbName
	 *
	 * @return MWCallableUpdate
	 */
	private function newDeferrableDelete( LinkTarget $linkTarget, $dbName ) {
		return new MWCallableUpdate(
			function () use ( $dbName, $linkTarget ) {
				$this->getRepo()->deletePage( $dbName, $linkTarget );
			},
			__METHOD__
		);
	}

	/**
	 * When one or more revisions of an article are restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete
	 *
	 * @param Title $title
	 */
	public function onArticleUndelete(
		Title $title
	) {
		$revision = $this->newRevisionRecordFromId( $title->getLatestRevID() );
		if ( !$this->isActionableTarget( $title ) || $revision == null ) {
			return;
		}

		$this->onContentChange( $title );
	}

	/**
	 * @param int $id
	 *
	 * @return null|RevisionRecord
	 */
	private function newRevisionRecordFromId( $id ) {
		return call_user_func( $this->newRevisionFromIdCallable, $id );
	}

	/**
	 * Occurs whenever a request to move an article is completed, after the database transaction
	 * commits.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @note for mediawiki 1.35+, this is run for the PageMoveComplete hook instead of the
	 * TitleMoveComplete hook
	 *
	 * @param LinkTarget $title
	 * @param LinkTarget $newTitle
	 */
	public function onTitleMoveComplete(
		LinkTarget $title,
		LinkTarget $newTitle
	) {
		$repo = $this->getRepo();
		if ( $this->isActionableTarget( $title ) ) {
			$repo->deletePage( $this->dbName, $title );
		}
		if ( $this->isActionableTarget( $newTitle ) ) {
			$repo->savePage( $this->dbName, $newTitle );
		}
	}

	/**
	 * Actionable targets have a namespace id that is:
	 *  - One of the default MediaWiki (between NS_MAIN and NS_CATEGORY_TALK
	 *  - Defined as a namespace to record in the configuration
	 * @param LinkTarget $linkTarget
	 * @return bool
	 */
	private function isActionableTarget( LinkTarget $linkTarget ) {
		$namespace = $linkTarget->getNamespace();
		return in_array( $namespace, $this->namespaces ) &&
			$namespace >= NS_MAIN && $namespace <= NS_CATEGORY_TALK;
	}

	/**
	 * @return CognateRepo
	 */
	private function getRepo() {
		return CognateServices::getRepo();
	}

	/**
	 * @param LinkTarget $linkTarget
	 */
	private function onContentChange( LinkTarget $linkTarget ) {
		$this->getRepo()->savePage( $this->dbName, $linkTarget );
	}

}
