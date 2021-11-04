<?php

namespace Cognate\Tests;

use Cognate\CacheInvalidator;
use Cognate\LocalJobSubmitJob;
use Job;
use JobQueueGroup;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Title;

/**
 * @covers \Cognate\CacheInvalidator
 * @covers \Cognate\LocalJobSubmitJob
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class CacheInvalidatorTest extends MediaWikiIntegrationTestCase {

	public function testJobIsQueued() {
		$title = Title::newFromText( 'Foo' );

		/** @var JobQueueGroup|MockObject $mockJobQueueGroup */
		$mockJobQueueGroup = $this->createMock( JobQueueGroup::class );
		$mockJobQueueGroup->expects( $this->once() )
			->method( 'lazyPush' )
			->with( $this->isInstanceOf( LocalJobSubmitJob::class ) )
			->will( $this->returnCallback( function ( Job $job ) use ( $title ) {
				$this->assertTrue( $job->getTitle()->equals( $title ) );
			} ) );

		$cacheInvalidator = new CacheInvalidator( $mockJobQueueGroup );
		$cacheInvalidator->invalidate( [ 'frwiktionary' ], $title );
	}

}
