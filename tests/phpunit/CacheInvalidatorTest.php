<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\LocalJobSubmitJob;
use Job;
use JobQueueGroup;
use MediaWikiTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Title;

class CacheInvalidatorTest extends MediaWikiTestCase {

	public function testJobIsQueued() {
		$title = Title::newFromText( 'Foo' );

		/** @var JobQueueGroup|PHPUnit_Framework_MockObject_MockObject $mockJobQueueGroup */
		$mockJobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();
		$mockJobQueueGroup->expects( $this->once() )
			->method( 'push' )
			->with( $this->isInstanceOf( LocalJobSubmitJob::class ) )
			->willReturnCallback( function( $job ) use ( $title ) {
				/** @var Job $job */
				$this->assertSame( $title, $job->getTitle() );
			} );

		$cacheInvalidator = new CacheInvalidator( $mockJobQueueGroup );
		$cacheInvalidator->invalidate( 'fr', $title );
	}
}