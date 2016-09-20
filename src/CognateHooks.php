<?php

use MediaWiki\MediaWikiServices;

class CognateHooks {

	public static function onPageContentSaveComplete() {
		call_user_func_array(
			[
				MediaWikiServices::getInstance()->getService( 'CognatePageHookHandler' ),
				'onPageContentSaveComplete'
			],
			func_get_args()
		);
		return true;
	}

	public static function onWikiPageDeletionUpdates() {
		call_user_func_array(
			[
				MediaWikiServices::getInstance()->getService( 'CognatePageHookHandler' ),
				'onWikiPageDeletionUpdates'
			],
			func_get_args()
		);
		return true;
	}

	public static function onArticleUndelete() {
		call_user_func_array(
			[
				MediaWikiServices::getInstance()->getService( 'CognatePageHookHandler' ),
				'onArticleUndelete'
			],
			func_get_args()
		);
		return true;
	}

	/**
	 * @param Title $title
	 * @param array $links
	 * @param array $linkFlags
	 * @return bool
	 */
	public static function onLanguageLinks( $title, &$links, &$linkFlags ) {
		global $wgCognateNamespaces, $wgLanguageCode;

		if ( !in_array( $title->getNamespace(), $wgCognateNamespaces ) ) {
			return true;
		}

		$store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
		$dbKey = $title->getDBkey();
		$languages = $store->getTranslationsForPage( $wgLanguageCode, $dbKey );

		foreach( $languages as $lang ) {
			if ( !isset( $links[$lang] ) ) {
				$links[$lang] = $lang . ':' . $dbKey;
			}
		}

		// TODO Move InterwikiSorter class from the wikibase extension to its own extension and use it to sort the language links
		// See https://git.wikimedia.org/blob/mediawiki%2Fextensions%2FWikibase/master/client%2Fincludes%2FInterwikiSorter.php
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

		$dbDir = __DIR__ . '/../db';
		$updater->addExtensionUpdate(
			array( 'addTable', 'inter_language_titles', "$dbDir/addInterLanguageTable.sql", true )
		);

		return true;
	}

	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, __DIR__ . '/tests/phpunit' );
		return true;
	}

}
