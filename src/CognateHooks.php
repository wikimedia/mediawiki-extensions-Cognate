<?php

namespace Cognate;

use Cognate\HookHandler\CognateParseHookHandler;
use MediaWiki\Content\Content;
use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use MediaWiki\Deferred\DeferrableUpdate;
use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Page\Hook\ArticleUndeleteHook;
use MediaWiki\Page\Hook\WikiPageDeletionUpdatesHook;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 */
class CognateHooks implements
	PageSaveCompleteHook,
	PageMoveCompleteHook,
	ContentAlterParserOutputHook,
	WikiPageDeletionUpdatesHook,
	ArticleUndeleteHook
{

	/**
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $userIdentity
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult unused
	 */
	public function onPageSaveComplete(
		$wikiPage,
		$userIdentity,
		$summary,
		$flags,
		$revisionRecord,
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
	public function onWikiPageDeletionUpdates(
		$page,
		$content,
		&$updates
	) {
		CognateServices::getPageHookHandler()
			->onWikiPageDeletionUpdates( $page->getTitle(), $updates );
	}

	/**
	 * @param Title $title
	 * @param bool $create
	 * @param string $comment
	 * @param int $oldPageId
	 * @param array $restoredPages
	 */
	public function onArticleUndelete(
		$title,
		$create,
		$comment,
		$oldPageId,
		$restoredPages
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
	public function onPageMoveComplete(
		$title,
		$newTitle,
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
	public function onContentAlterParserOutput(
		$content,
		$title,
		$parserOutput
	) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handler = CognateParseHookHandler::newFromGlobalState();
		$handler->doContentAlterParserOutput( $title, $parserOutput );
	}
}
