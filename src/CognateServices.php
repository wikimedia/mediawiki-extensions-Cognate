<?php

namespace Cognate;

use Cognate\HookHandler\CognatePageHookHandler;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 * @codeCoverageIgnore
 */
class CognateServices {

	/**
	 * @param MediaWikiServices|null $services
	 * @param string $name
	 * phpcs:ignore MediaWiki.Commenting.FunctionComment.ObjectTypeHintReturn
	 * @return object
	 */
	private static function getService( ?MediaWikiServices $services, $name ) {
		if ( $services === null ) {
			$services = MediaWikiServices::getInstance();
		}
		return $services->getService( $name );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return LoggerInterface
	 */
	public static function getLogger( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateLogger' );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return CognateRepo
	 */
	public static function getRepo( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateRepo' );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return ConnectionManager
	 */
	public static function getConnectionManager( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateConnectionManager' );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return CognateStore
	 */
	public static function getStore( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateStore' );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return CognatePageHookHandler
	 */
	public static function getPageHookHandler( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognatePageHookHandler' );
	}

	/**
	 * @param MediaWikiServices|null $services
	 * @return CacheInvalidator
	 */
	public static function getCacheInvalidator( MediaWikiServices $services = null ) {
		return self::getService( $services, 'CognateCacheInvalidator' );
	}

}
