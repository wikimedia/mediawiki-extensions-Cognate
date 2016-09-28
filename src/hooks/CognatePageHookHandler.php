<?php

namespace Cognate;

use Content;
use DeferrableUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MWCallableUpdate;
use Revision;
use Status;
use Title;
use TitleValue;
use User;
use Wikimedia\Assert\Assert;
use WikiPage;

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class CognatePageHookHandler {

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var int[]
	 */
	private $namespaces;

	/**
	 * CognatePageHookHandler constructor.
	 *
	 * @param int[] $namespaces array of namespace ids the hooks should operate on
	 * @param string $languageCode the language code of the current site
	 */
	public function __construct( array $namespaces, $languageCode ) {
		$this->namespaces = $namespaces;
		$this->languageCode = $languageCode;
	}

	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 *
	 * @param WikiPage $page
	 * @param User $user
	 * @param Content $content
	 * @param string $summary
	 * @param boolean $isMinor
	 * @param boolean $isWatch
	 * @param mixed $section Deprecated
	 * @param integer $flags
	 * @param Revision|null $revision
	 * @param Status $status
	 * @param integer $baseRevId
	 */
	public function onPageContentSaveComplete(
		WikiPage $page,
		User $user,
		Content $content,
		$summary,
		$isMinor,
		$isWatch,
		$section,
		$flags,
		$revision,
		Status $status,
		$baseRevId
	) {
		$titleValue = $page->getTitle()->getTitleValue();
		if ( $this->isActionableTarget( $titleValue ) ) {
			$this->getStore()->savePage( $this->languageCode, $titleValue );
		}
	}

	/**
	 * Manipulate the list of DataUpdates to be applied when a page is deleted
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/WikiPageDeletionUpdates
	 *
	 * @param WikiPage $page
	 * @param Content|null $content
	 * @param DeferrableUpdate[] $updates
	 */
	public function onWikiPageDeletionUpdates(
		WikiPage $page,
		Content $content = null,
		array &$updates
	) {
		$titleValue = $page->getTitle()->getTitleValue();
		$language = $this->languageCode;
		if ( $this->isActionableTarget( $titleValue ) ) {
			$updates[] = $this->newDeferrableDelete( $titleValue, $language );
		}
	}

	/**
	 * @param TitleValue $titleValue
	 * @param string $language
	 *
	 * @return MWCallableUpdate
	 */
	private function newDeferrableDelete( TitleValue $titleValue, $language ) {
		return new MWCallableUpdate(
			function () use ( $language, $titleValue ){
				$this->getStore()->deletePage( $language, $titleValue );
			},
			__METHOD__
		);
	}

	/**
	 * When one or more revisions of an article are restored
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleUndelete
	 *
	 * @param Title $title
	 * @param bool $create
	 * @param string $comment
	 * @param int $oldPageId
	 */
	public function onArticleUndelete(
		Title $title,
		$create,
		$comment,
		$oldPageId
	) {
		$titleValue = $title->getTitleValue();
		if ( $this->isActionableTarget( $titleValue ) ) {
			$this->getStore()->savePage( $this->languageCode, $titleValue );
		}
	}

	/**
	 * Occurs whenever a request to move an article is completed, after the database transaction commits.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 *
	 * @param Title $title
	 * @param Title $newTitle
	 * @param User $user
	 * @param int $oldid
	 * @param int $newid
	 * @param string $reason
	 * @param Revision $revision
	 */
	public function onTitleMoveComplete(
		Title $title,
		Title $newTitle,
		User $user,
		$oldid,
		$newid,
		$reason,
		Revision $revision
	) {
		$oldTitleValue = $title->getTitleValue();
		$newTitleValue = $newTitle->getTitleValue();
		$store = $this->getStore();
		if ( $this->isActionableTarget( $oldTitleValue ) ) {
			$store->deletePage( $this->languageCode, $oldTitleValue );
		}
		if ( $this->isActionableTarget( $newTitleValue ) ) {
			$store->savePage( $this->languageCode, $newTitleValue );
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
	 * @return CognateStore
	 */
	private function getStore() {
		return MediaWikiServices::getInstance()->getService( 'CognateStore' );
	}

}