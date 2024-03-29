<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use Cognate\HookHandler\CognatePageHookHandler;
use MediaWiki\MediaWikiServices;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class ServiceWiringTest extends \MediaWikiIntegrationTestCase {

	public static function provideServices() {
		return [
			[ 'CognateRepo', CognateRepo::class ],
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
