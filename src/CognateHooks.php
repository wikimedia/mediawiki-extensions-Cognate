<?php

namespace Cognate;

use Cognate\HookHandler\CognateParseHookHandler;
use Content;
use DatabaseUpdater;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use ParserOutput;
use Title;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 */
class CognateHooks {

	/**
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $userIdentity
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult unused
	 */
	public static function onPageSaveComplete(
		WikiPage $wikiPage,
		UserIdentity $userIdentity,
		string $summary,
		int $flags,
		RevisionRecord $revisionRecord,
		$editResult
	) {
		CognateServices::getPageHookHandler()->onPageContentSaveComplete(
			$wikiPage->getTitle(),
			$revisionRecord
		);
	}

	/**
	 * @param WikiPage $page
	 * @param Content|null $content
	 * @param DeferrableUpdate[] &$updates
	 */
	public static function onWikiPageDeletionUpdates(
		WikiPage $page,
		$content,
		array &$updates
	) {
		CognateServices::getPageHookHandler()
			->onWikiPageDeletionUpdates( $page->getTitle(), $updates );
	}

	/**
	 * @param Title $title
	 * @param bool $create
	 * @param string $comment
	 * @param int $oldPageId
	 */
	public static function onArticleUndelete(
		Title $title,
		$create,
		$comment,
		$oldPageId
	) {
		CognateServices::getPageHookHandler()->onArticleUndelete( $title );
	}

	/**
	 * @param LinkTarget $title
	 * @param LinkTarget $newTitle
	 * @param UserIdentity $userIdentity
	 * @param int $oldid
	 * @param int $newid
	 * @param string $reason
	 * @param RevisionRecord $nullRevisionRecord
	 */
	public static function onPageMoveComplete(
		LinkTarget $title,
		LinkTarget $newTitle,
		$userIdentity,
		$oldid,
		$newid,
		$reason,
		$nullRevisionRecord
	) {
		CognateServices::getPageHookHandler()->onTitleMoveComplete( $title, $newTitle );
	}

	/**
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public static function onContentAlterParserOutput(
		$content,
		Title $title,
		ParserOutput $parserOutput
	) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handler = CognateParseHookHandler::newFromGlobalState();
		$handler->doContentAlterParserOutput( $title, $parserOutput );
	}

	/**
	 * Run database updates
	 *
	 * @see CognateUpdater regarding the complexities of this hook
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		// Add our updater
		$updater->addExtensionUpdate( [ [ CognateUpdater::class, 'update' ] ] );
	}
}
