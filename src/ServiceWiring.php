<?php

namespace Cognate;

use Cognate\HookHandler\CognatePageHookHandler;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;

/**
 * Cognate wiring for MediaWiki services.
 */

return [
	'CognateLogger' => static function ( MediaWikiServices $services ): LoggerInterface {
		return LoggerFactory::getInstance( 'Cognate' );
	},

	'CognateRepo' => static function ( MediaWikiServices $services ): CognateRepo {
		return new CognateRepo(
			CognateServices::getStore( $services ),
			CognateServices::getCacheInvalidator( $services ),
			$services->getTitleFormatter(),
			$services->getStatsFactory(),
			CognateServices::getLogger( $services )
		);
	},

	'CognateStore' => static function ( MediaWikiServices $services ): CognateStore {
		return new CognateStore(
			$services->getConnectionProvider(),
			new StringNormalizer(),
			new StringHasher(),
			$services->getMainConfig()->get( 'CognateReadOnly' )
		);
	},

	'CognatePageHookHandler' => static function ( MediaWikiServices $services ): CognatePageHookHandler {
		return new CognatePageHookHandler(
			$services->getMainConfig()->get( 'CognateNamespaces' ),
			$services->getMainConfig()->get( 'DBname' )
		);
	},

	'CognateCacheInvalidator' => static function ( MediaWikiServices $services ): CacheInvalidator {
		return new CacheInvalidator( $services->getJobQueueGroup() );
	},
];
