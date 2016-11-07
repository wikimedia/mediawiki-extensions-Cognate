<?php

namespace Cognate;

use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use Title;

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

	public static function onWikiPageDeletionUpdates( $page, $content, &$updates ) {
		MediaWikiServices::getInstance()
			->getService( 'CognatePageHookHandler' )
			->onWikiPageDeletionUpdates( $page, $content, $updates );
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

		$presentLanguages = [];
		foreach ( $links as $linkString ) {
			$linkParts = explode( ':', $linkString, 2 );
			$presentLanguages[$linkParts[0]] = true;
		}

		/** @var CognateRepo $repo */
		$repo = MediaWikiServices::getInstance()->getService( 'CognateRepo' );
		$cognateLanguages = $repo->getLinksForPage( $wgLanguageCode, $title );

		$dbKey = $title->getDBkey();
		foreach ( $cognateLanguages as $lang ) {
			if ( !array_key_exists( $lang, $presentLanguages ) ) {
				$links[] = $lang . ':' . $dbKey;
			}
		}

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

}
