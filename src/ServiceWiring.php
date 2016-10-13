<?php

namespace Cognate;

use MediaWiki\MediaWikiServices;

/**
 * Cognate wiring for MediaWiki services.
 */


return [
	'CognateStore' => function( MediaWikiServices $services ) {
		$lbFactory = $services->getDBLoadBalancerFactory();
		$cognateDb = $services->getMainConfig()->get( 'CognateDb' );
		$cognateCluster = $services->getMainConfig()->get( 'CognateCluster' );
		if ( $cognateCluster ) {
			$lb = $lbFactory->getExternalLB( $cognateCluster, $cognateDb );
		} else {
			$lb = $lbFactory->getMainLB( $cognateDb );
		}
		return new CognateStore(
			$lb,
			new StringNormalizer()
		);
	},

	'CognatePageHookHandler' => function( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'LanguageCode' )
		);
	},
];