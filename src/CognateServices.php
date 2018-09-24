<?php

namespace Cognate;

use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 * @codeCoverageIgnore
 */
class CognateServices {

	private static function getService( MediaWikiServices $services = null, $name ) {
		if ( $services === null ) {
			$services = MediaWikiServices::getInstance();
		}
		return $services->getService( $name );
	}

	/** @return LoggerInterface */
	public static function getLogger( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateLogger' );
	}

	/** @return CognateRepo */
	public static function getRepo( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateRepo' );
	}

	/** @return ConnectionManager */
	public static function getConnectionManager( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateConnectionManager' );
	}

	/** @return CognateStore */
	public static function getStore( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateStore' );
	}

	/** @return CognatePageHookHandler */
	public static function getPageHookHandler( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognatePageHookHandler' );
	}

	/** @return CacheInvalidator */
	public static function getCacheInvalidator( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateCacheInvalidator' );
	}

}
