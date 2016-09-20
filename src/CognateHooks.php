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

	public static function onTitleMoveComplete() {
		call_user_func_array(
			[
				MediaWikiServices::getInstance()->getService( 'CognatePageHookHandler' ),
				'onTitleMoveComplete'
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

		/** @var CognateStore $store */
		$store = MediaWikiServices::getInstance()->getService( 'CognateStore' );
		$languages = $store->getLinksForPage( $wgLanguageCode, $title );

		$dbKey = $title->getDBkey();
		foreach ( $languages as $lang ) {
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
	 * Only runs the update when both $wgCognateDb and $wgCognateCluster are false
	 * i.e. for testing.
	 *
	 * @param DatabaseUpdater $updater DatabaseUpdater object
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater = null ) {
		global $wgCognateDb, $wgCognateCluster;

		if ( $wgCognateDb === false && $wgCognateCluster === false ) {
			$updater->addExtensionUpdate(
				[ 'addTable', 'cognate_titles', __DIR__ . '/../db/addCognateTitles.sql', true ]
			);
		}

		return true;
	}

	public static function onUnitTestsList( &$files ) {
		$files = array_merge( $files, __DIR__ . '/tests/phpunit' );
		return true;
	}

}
