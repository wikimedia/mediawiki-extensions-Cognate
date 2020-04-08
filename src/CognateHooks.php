<?php

namespace Cognate;

use Content;
use DatabaseUpdater;
use DeferrableUpdate;
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
	 * @param WikiPage $page
	 * @param \User $user
	 * @param Content $content
	 * @param string $summary
	 * @param bool $isMinor
	 * @param bool $isWatch
	 * @param string $section
	 * @param int $flags
	 * @param ?\Revision $revision
	 * @param \Status $status
	 * @param int $baseRevId
	 * @param int|null $undidRevId
	 */
	public static function onPageContentSaveComplete(
		WikiPage $page,
		$user,
		Content $content,
		$summary,
		$isMinor,
		$isWatch,
		$section,
		$flags,
		?\Revision $revision,
		$status,
		$baseRevId,
		$undidRevId = null
	) {
		CognateServices::getPageHookHandler()->onPageContentSaveComplete(
			$page->getTitle(),
			$content->isRedirect(),
			(bool)( $flags & EDIT_NEW ),
			$revision
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

	public static function onArticleUndelete(
		Title $title,
		$create,
		$comment,
		$oldPageId
	) {
		CognateServices::getPageHookHandler()->onArticleUndelete( $title );
	}

	public static function onTitleMoveComplete(
		Title $title,
		Title $newTitle,
		$user,
		$oldid,
		$newid,
		$reason,
		$nullRevision
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
