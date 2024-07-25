<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\CognateRepo;
use Cognate\CognateStore;
use Cognate\HookHandler\CognatePageHookHandler;

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
		$services = $this->getServiceContainer();
		$service1 = $services->getService( $serviceName );
		$service2 = $services->getService( $serviceName );

		$this->assertInstanceOf( $expectedClass, $service1 );
		$this->assertInstanceOf( $expectedClass, $service2 );
		$this->assertSame( $service1, $service2 );
	}

}
