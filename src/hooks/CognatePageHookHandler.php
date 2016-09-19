<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\Assert\Assert;

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
	 * @param WikiPage $article
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
		WikiPage $article,
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
		if ( $article->getTitle()->inNamespaces( $this->namespaces ) ) {
			$store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
			$store->savePage( $this->languageCode, $article->getTitle()->getDBkey() );
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
		$title = $page->getTitle();
		$language = $this->languageCode;
		if ( $title->inNamespaces( $this->namespaces ) ) {
			$updates[] = new MWCallableUpdate(
				function () use ( $title, $language ){
					$store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
					$store->deletePage( $language, $title->getDBkey() );
				},
				__METHOD__
			);
		}
	}

}