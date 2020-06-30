<?php

namespace Cognate;

use Content;
use DatabaseUpdater;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Revision\RevisionRecord;
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
	 * Callback on extension registration
	 *
	 * Register hooks based on version to keep support for mediawiki versions before 1.35
	 */
	public static function onRegistration() {
		global $wgHooks;

		if ( version_compare( MW_VERSION, '1.35', '>=' ) ) {
			$wgHooks['PageSaveComplete'][] = 'Cognate\\CognateHooks::onPageSaveComplete';
			$wgHooks['PageMoveComplete'][] = 'Cognate\\CognateHooks::onPageMoveComplete';
		} else {
			$wgHooks['PageContentSaveComplete'][] = 'Cognate\\CognateHooks::onPageContentSaveComplete';
			$wgHooks['TitleMoveComplete'][] = 'Cognate\\CognateHooks::onTitleMoveComplete';
		}
	}

	/**
	 * Only run in versions of mediawiki begining 1.35; before 1.35, ::onPageContentSaveComplete
	 * is used used
	 *
	 * @note paramaters include classes not available before 1.35, so for those typehints
	 * are not used. The variable name reflects the class
	 *
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $userIdentity
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param mixed $editResult unused
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
			$wikiPage->getContent()->isRedirect(),
			(bool)( $flags & EDIT_NEW ),
			$revisionRecord
		);
	}

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
			$revision ? $revision->getRevisionRecord() : null
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
