<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\CognatePageHookHandler;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\ConnectionManager;

/**
 * @covers ServiceWiring.php
 *
 * @license GNU GPL v2+
 * @author Addshore
 */
class ServiceWiringTest extends \MediaWikiTestCase {

	public function provideServices() {
		return [
			[ 'CognateRepo', CognateRepo::class ],
			[ 'CognateConnectionManager', ConnectionManager::class ],
			[ 'CognateStore', CognateStore::class ],
			[ 'CognatePageHookHandler', CognatePageHookHandler::class ],
			[ 'CognateCacheInvalidator', CacheInvalidator::class ],
		];
	}

	/**
	 * @dataProvider provideServices
	 */
	public function testServiceWiring( $serviceName, $expectedClass ) {
		$service1 = MediaWikiServices::getInstance()->getService( $serviceName );
		$service2 = MediaWikiServices::getInstance()->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service1 );
		$this->assertInstanceOf( $expectedClass, $service2 );
		$this->assertSame( $service1, $service2 );
	}

}
