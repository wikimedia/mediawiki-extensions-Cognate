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
		global $wgPageTitleInterlanguageWiki, $wgLanguageCode;
		$interlanguage = new PageTitleInterlanguageExtension( wfGetDB( DB_MASTER, [], $wgPageTitleInterlanguageWiki ) );
		$interlanguage->savePage( $wgLanguageCode, $article->getTitle()->getDBkey() );
		return true;
	}

	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, Content $content = null, LogEntry $logEntry ) {
		// TODO remove language link from central storage
	}

	public static function onSkinTemplateOutputPageBeforeExec( &$skin, &$template ) {
		// TODO insert language links in template data, similar to https://git.wikimedia.org/blob/mediawiki%2Fextensions%2FInterlanguage/master/InterlanguageExtension.php#L293
	}

	/**
	 * Run database updates
	 * @param DatabaseUpdater $updater DatabaseUpdater object
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater = null ) {
		$dbDir = __DIR__ . '/db';
		$updater->addExtensionUpdate( array( 'addTable', 'page_assessments', "$dbDir/addInterLanguageTable.sql", true ) );
		return true;
	}
	
	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, glob( __DIR__ . '/tests/phpunit/*Test.php' ) );
		return true;
	}
}