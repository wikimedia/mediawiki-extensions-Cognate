<?php

// TODO Make this testable by creating non-static methods and using an instance in the static methods
// TODO Split into two hook handler classes, one handling page-reltaed stuff, the other handling database update and tests
class CognateHooks {
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
	 * @param $section Deprecated
	 * @param integer $flags
	 * @param {Revision|null} $revision
	 * @param Status $status
	 * @param integer $baseRevId
	 *
	 * @return boolean
	 */
	public static function onPageContentSaveComplete( WikiPage $article, User $user, Content $content,
													  $summary, $isMinor, $isWatch, $section, $flags, $revision,
													  Status $status, $baseRevId ) {
		global $wgCognateWiki, $wgCognateNamespaces, $wgLanguageCode;
		$title = $article->getTitle();
		if ( !in_array( $title->getNamespace(), $wgCognateNamespaces ) ) {
			return true;
		}
		$interlanguage = new CognateStore( wfGetLB(), $wgCognateWiki );
		$interlanguage->savePage( $wgLanguageCode, $article->getTitle()->getDBkey() );
		return true;
	}

	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, Content $content = null, LogEntry $logEntry ) {
		// TODO remove language link from central storage
	}

	/**
	 * @param Title $title
	 * @param array $links
	 * @param array $linkFlags
	 * @return bool
	 */
	public static function onLanguageLinks( $title, &$links, &$linkFlags ) {
		global $wgCognateWiki, $wgCognateNamespaces, $wgLanguageCode;
		if ( !in_array( $title->getNamespace(), $wgCognateNamespaces ) ) {
			return true;
		}
		$interlanguage = new CognateStore( wfGetLB(), $wgCognateWiki );
		$dbKey = $title->getDBkey();
		$languages = $interlanguage->getTranslationsForPage( $wgLanguageCode, $dbKey );
		foreach( $languages as $lang ) {
			if ( !isset( $links[$lang] ) ) {
				$links[$lang] = $lang . ':' . $dbKey;
			}
		}
		return true;
	}

	/**
	 * Run database updates
	 *
	 * Only runs the update when $wgCognateWiki is false (i.e. for testing and
	 * when updating the "main" wiktionary project.
	 *
	 * @param DatabaseUpdater $updater DatabaseUpdater object
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater = null ) {
		global $wgCognateWiki;
		if ( $wgCognateWiki ) {
			return true;
		}

		$dbDir = __DIR__ . '/db';
		$updater->addExtensionUpdate( array( 'addTable', 'page_assessments', "$dbDir/addInterLanguageTable.sql", true ) );
		return true;
	}
	
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, __DIR__ . '/tests/phpunit' );
		return true;
	}
}