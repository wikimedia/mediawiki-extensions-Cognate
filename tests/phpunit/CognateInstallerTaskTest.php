<?php

namespace Cognate\Tests;

use Cognate\CognateInstallerTask;
use MediaWiki\Installer\Task\ITaskContext;
use MediaWiki\Installer\Task\TaskFactory;
use MediaWiki\MainConfigNames;

/**
 * @group Database
 * @covers \Cognate\CognateInstallerTask
 */
class CognateInstallerTaskTest extends \MediaWikiIntegrationTestCase {
	public function testExecute() {
		$this->overrideConfigValues( [
			MainConfigNames::DBname => 'new_wiki',
			MainConfigNames::DBmwschema => null,
			MainConfigNames::DBprefix => '',
		] );

		$services = $this->getServiceContainer();
		$context = $this->createMock( ITaskContext::class );

		$context->method( 'getConfigVar' )
			->with( 'LocalInterwikis' )
			->willReturn( [ 'new' ] );

		$context->method( 'getProvision' )
			->with( 'services' )
			->willReturn( $services );

		$taskFactory = new TaskFactory( $services->getObjectFactory(), $context );
		$task = $taskFactory->create( [ 'class' => CognateInstallerTask::class ] );
		$task->execute();
		$this->newSelectQueryBuilder()
			->select( [ 'cgsi_key', 'cgsi_dbname', 'cgsi_interwiki' ] )
			->from( 'cognate_sites' )
			->assertRowValue( [ -8431553790134745048, 'new_wiki', 'new' ] );
	}
}
