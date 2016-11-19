<?php

namespace Cognate;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * Cognate wiring for MediaWiki services.
 */

return [
	'CognateRepo' => function( MediaWikiServices $services ) {
		/** @var CognateStore $store */
		$store = $services->getService( 'CognateStore' );
		/** @var CacheInvalidator $cacheInvalidator */
		$cacheInvalidator = $services->getService( 'CognateCacheInvalidator' );

		return new CognateRepo(
			$store,
			$cacheInvalidator,
			$services->getTitleFormatter()
		);
	},

	'CognateConnectionManager' => function( MediaWikiServices $services ) {
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

	'CognateStore' => function( MediaWikiServices $services ) {
		/** @var ConnectionManager $connectionManager */
		$connectionManager = $services->getService( 'CognateConnectionManager' );

		return new CognateStore(
			$connectionManager,
			new StringNormalizer(),
			new StringHasher()
		);
	},

	'CognatePageHookHandler' => function( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'DBname' )
		);
	},

	'CognateCacheInvalidator' => function( MediaWikiServices $services ) {
		return new CacheInvalidator( JobQueueGroup::singleton() );
	},
];
