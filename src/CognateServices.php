<?php

declare( strict_types = 1 );

namespace Cognate;

use Cognate\HookHandler\CognatePageHookHandler;
use MediaWiki\MediaWikiServices;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognateServices {

	public static function getLogger( ContainerInterface $services = null ): LoggerInterface {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'CognateLogger' );
	}

	public static function getRepo( ContainerInterface $services = null ): CognateRepo {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'CognateRepo' );
	}

	public static function getStore( ContainerInterface $services = null ): CognateStore {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'CognateStore' );
	}

	public static function getPageHookHandler( ContainerInterface $services = null ): CognatePageHookHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'CognatePageHookHandler' );
	}

	public static function getCacheInvalidator( ContainerInterface $services = null ): CacheInvalidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'CognateCacheInvalidator' );
	}

}
