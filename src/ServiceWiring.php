<?php

namespace Cognate;

use JobQueueGroup;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Cognate wiring for MediaWiki services.
 */

return [
	'CognateLogger' => function ( MediaWikiServices $services ) {
		return LoggerFactory::getInstance( 'Cognate' );
	},

	'CognateRepo' => function ( MediaWikiServices $services ) {
		$repo = new CognateRepo(
			CognateServices::getStore(),
			CognateServices::getCacheInvalidator(),
			$services->getTitleFormatter(),
			CognateServices::getLogger()
		);

		$repo->setStatsdDataFactory( $services->getStatsdDataFactory() );

		return $repo;
	},

	'CognateConnectionManager' => function ( MediaWikiServices $services ) {
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

	'CognateStore' => function ( MediaWikiServices $services ) {
		return new CognateStore(
			CognateServices::getConnectionManager(),
			new StringNormalizer(),
			new StringHasher(),
			$services->getMainConfig()->get( 'CognateReadOnly' )
		);
	},

	'CognatePageHookHandler' => function ( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'DBname' )
		);
	},

	'CognateCacheInvalidator' => function ( MediaWikiServices $services ) {
		return new CacheInvalidator( JobQueueGroup::singleton() );
	},
];
