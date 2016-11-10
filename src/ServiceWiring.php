<?php

namespace Cognate;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;

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
			$cacheInvalidator
		);
	},

	'CognateStore' => function( MediaWikiServices $services ) {
		$lbFactory = $services->getDBLoadBalancerFactory();
		$cognateDb = $services->getMainConfig()->get( 'CognateDb' );
		$cognateCluster = $services->getMainConfig()->get( 'CognateCluster' );
		if ( $cognateCluster ) {
			$lb = $lbFactory->getExternalLB( $cognateCluster );
		} else {
			$lb = $lbFactory->getMainLB( $cognateDb );
		}

		return new CognateStore(
			$lb,
			$cognateDb,
			new StringNormalizer()
		);
	},

	'CognatePageHookHandler' => function( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'LanguageCode' )
		);
	},

	'CognateCacheInvalidator' => function( MediaWikiServices $services ) {
		return new CacheInvalidator( JobQueueGroup::singleton() );
	},
];
