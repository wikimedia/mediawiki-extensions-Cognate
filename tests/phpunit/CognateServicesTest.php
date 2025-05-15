<?php

declare( strict_types = 1 );

namespace Cognate\Tests;

use Cognate\CognateServices;
use MediaWiki\Tests\ExtensionServicesTestBase;

/**
 * @covers \Cognate\CognateServices
 *
 * @license GPL-2.0-or-later
 */
class CognateServicesTest extends ExtensionServicesTestBase {

	protected static string $className = CognateServices::class;

	protected string $serviceNamePrefix = 'Cognate';

}
