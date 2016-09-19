<?php
/**
 * Cognate wiring for MediaWiki services.
 */

use MediaWiki\MediaWikiServices;

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
		return new CognateStore( $lb );
	},

	'CognatePageHookHandler' => function( MediaWikiServices $services ) {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'LanguageCode' )
		);
	},
];
