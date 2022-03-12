<?php

namespace Cognate;

use Cognate\HookHandler\CognatePageHookHandler;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Cognate wiring for MediaWiki services.
 */

return [
	'CognateLogger' => static function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'Cognate' );
	},

	'CognateRepo' => static function ( MediaWikiServices $services ) {
		$repo = new CognateRepo(
			CognateServices::getStore( $services ),
			CognateServices::getCacheInvalidator( $services ),
			$services->getTitleFormatter(),
			CognateServices::getLogger( $services )
		);

		$repo->setStatsdDataFactory( $services->getStatsdDataFactory() );

		return $repo;
	},

	'CognateConnectionManager' => static function ( MediaWikiServices $services ) {
		$lbFactory = $services->getDBLoadBalancerFactory();
		$cognateDb = $services->getMainConfig()->get( 'CognateDb' );
		$cognateCluster = $services->getMainConfig()->get( 'CognateCluster' );

		if ( $cognateCluster ) {
			$lb = $lbFactory->getExternalLB( $cognateCluster );
		} else {
			$lb = $lbFactory->getMainLB( $cognateDb );
		}

		return new ConnectionManager(
			$lb,
			$cognateDb
		);
	},

	'CognateStore' => static function ( MediaWikiServices $services ) {
		return new CognateStore(
			CognateServices::getConnectionManager( $services ),
			new StringNormalizer(),
			new StringHasher(),
			$services->getMainConfig()->get( 'CognateReadOnly' )
		);
	},

	'CognatePageHookHandler' => static function ( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'DBname' )
		);
	},

	'CognateCacheInvalidator' => static function ( MediaWikiServices $services ) {
		return new CacheInvalidator( $services->getJobQueueGroup() );
	},
];
