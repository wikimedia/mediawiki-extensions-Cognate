<?php

class PageTitleInterlanguageHooks {
	public static function onPageContentSaveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		
		// TODO update central storage
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