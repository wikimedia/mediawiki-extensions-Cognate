<?php

namespace Cognate;

use Content;
use DatabaseUpdater;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * @license GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Addshore
 */
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
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public static function onContentAlterParserOutput(
		Content $content,
		Title $title,
		ParserOutput $parserOutput
	) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handler = CognateParseHookHandler::newFromGlobalState();
		$handler->doContentAlterParserOutput( $title, $parserOutput );
	}

	/**
	 * Run database updates
	 *
	 * @see CognateUpdater regarding the complexities of this hook
	 *
	 * @param DatabaseUpdater $updater
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgCognateDb, $wgCognateCluster;

		// Avoid running this code again when calling CognateUpdater::newForDB
		static $hasRunOnce = false;
		if ( $hasRunOnce ) {
			return true;
		} else {
			$hasRunOnce = true;
		}

		// Setup and run our own updater
		if ( $wgCognateDb === false && $wgCognateCluster === false ) {
			$cognateUpdater = CognateUpdater::newForDB( $updater->getDB() );
		} else {
			$services = MediaWikiServices::getInstance();
			if ( $wgCognateCluster !== false ) {
				$loadBalancerFactory = $services->getDBLoadBalancerFactory();
				$loadBalancer = $loadBalancerFactory->getExternalLB( $wgCognateCluster );
			} else {
				$loadBalancer = $services->getDBLoadBalancer();
			}
			$cognateDatabase = $loadBalancer->getConnection( LoadBalancer::DB_MASTER, [], $wgCognateDb );
			$cognateUpdater = CognateUpdater::newForCognateDB( $updater->getDB(), $cognateDatabase );
		}

		$cognateUpdater->addExtensionUpdate(
			[ 'addTable', 'cognate_pages', __DIR__ . '/../db/addCognatePages.sql', true ]
		);
		$cognateUpdater->addExtensionUpdate(
			[ 'addTable', 'cognate_titles', __DIR__ . '/../db/addCognateTitles.sql', true ]
		);
		$cognateUpdater->addExtensionUpdate(
			[ 'addTable', 'cognate_sites', __DIR__ . '/../db/addCognateSites.sql', true ]
		);

		$updater->addExtensionUpdate(
			[ [ CognateUpdater::class, 'realDoUpdates' ], $cognateUpdater ]
		);

		return true;
	}

}
