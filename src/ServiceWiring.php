<?php
/**
 * Cognate wiring for MediaWiki services.
 */

use MediaWiki\MediaWikiServices;

return [
	'CognateStore' => function( MediaWikiServices $services ) {
		return new CognateStore(
			$services->getDBLoadBalancer(),
			$services->getMainConfig()->get( 'CognateWiki' )
		);
	},
];
