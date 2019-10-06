<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\LocalJobSubmitJob;
use Job;
use JobQueueGroup;
use MediaWikiTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;

/**
 * @covers \Cognate\CacheInvalidator
 * @covers \Cognate\LocalJobSubmitJob
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CacheInvalidatorTest extends MediaWikiTestCase {

	public function testJobIsQueued() {
		$title = Title::newFromText( 'Foo' );

		/** @var JobQueueGroup|MockObject $mockJobQueueGroup */
		$mockJobQueueGroup = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->getMock();
		$mockJobQueueGroup->expects( $this->once() )
			->method( 'push' )
			->with( $this->isInstanceOf( LocalJobSubmitJob::class ) )
			->will( $this->returnCallback( function ( Job $job ) use ( $title ) {
				$this->assertTrue( $job->getTitle()->equals( $title ) );
			} ) );

		$cacheInvalidator = new CacheInvalidator( $mockJobQueueGroup );
		$cacheInvalidator->invalidate( [ 'frwiktionary' ], $title );
	}

}
