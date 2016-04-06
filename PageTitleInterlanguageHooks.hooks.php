<?php

class PageTitleInterlanguageHooks {
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
	public static function onPageContentSaveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		global $wgPageTitleInterlanguageWiki, $wgPageTitleInterlanguageNamespaces, $wgLanguageCode;
		$title = $article->getTitle();
		if ( !in_array( $title->getNamespace(), $wgPageTitleInterlanguageNamespaces ) ) {
			return true;
		}
		$interlanguage = new PageTitleInterlanguageExtension( wfGetDB( DB_MASTER, [], $wgPageTitleInterlanguageWiki ) );
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
		global $wgPageTitleInterlanguageWiki, $wgPageTitleInterlanguageNamespaces, $wgLanguageCode;
		if ( !in_array( $title->getNamespace(), $wgPageTitleInterlanguageNamespaces ) ) {
			return true;
		}
		$interlanguage = new PageTitleInterlanguageExtension( wfGetDB( DB_MASTER, [], $wgPageTitleInterlanguageWiki ) );
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
	 * @param DatabaseUpdater $updater DatabaseUpdater object
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater = null ) {
		global $wgPageTitleInterlanguageWiki;
		$interlanguageDb = wfGetDB( DB_MASTER, [], $wgPageTitleInterlanguageWiki );
		if ( $updater->getDB()->getDBname() != $interlanguageDb->getDBname() ) {
			return true;
		}
		// TODO: Ask someone if this will ever be executed when $wgPageTitleInterlanguageWiki is set

		$dbDir = __DIR__ . '/db';
		$updater->addExtensionUpdate( array( 'addTable', 'page_assessments', "$dbDir/addInterLanguageTable.sql", true ) );
		return true;
	}
	
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, glob( __DIR__ . '/tests/phpunit/*Test.php' ) );
		return true;
	}
}