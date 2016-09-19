<?php
use MediaWiki\MediaWikiServices;

/**
 * @license GNU GPL v2+
 * @author Addshore
 */
class ServiceWiringTest extends MediaWikiTestCase {

	public function provideServices() {
		return [
			[ 'CognateStore', CognateStore::class ],
			[ 'CognatePageHookHandler', CognatePageHookHandler::class ],
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
