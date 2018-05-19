<?php

namespace Cognate;

use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CognateServices {

	/** @return LoggerInterface */
	public static function getLogger() {
		return MediaWikiServices::getInstance()->getService( 'CognateLogger' );
	}

	/** @return CognateRepo */
	public static function getRepo() {
		return MediaWikiServices::getInstance()->getService( 'CognateRepo' );
	}

	/** @return ConnectionManager */
	public static function getConnectionManager() {
		return MediaWikiServices::getInstance()->getService( 'CognateConnectionManager' );
	}

	/** @return CognateStore */
	public static function getStore() {
		return MediaWikiServices::getInstance()->getService( 'CognateStore' );
	}

	/** @return CognatePageHookHandler */
	public static function getPageHookHandler() {
		return MediaWikiServices::getInstance()->getService( 'CognatePageHookHandler' );
	}

	/** @return CacheInvalidator */
	public static function getCacheInvalidator() {
		return MediaWikiServices::getInstance()->getService( 'CognateCacheInvalidator' );
	}

}
