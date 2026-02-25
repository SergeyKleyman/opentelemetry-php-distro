<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\Util;

use OTelDistroTests\Util\OTelDistroExtensionUtil;
use OTelDistroTests\Util\PHPUnitExtensionBase;
use OTelDistroTests\Util\TestsInfraException;

/**
 * @noinspection PhpUnused
 *
 * Referenced in PHPUnit's configuration file - phpunit.xml
 */
final class UnitTestsPHPUnitExtension extends PHPUnitExtensionBase
{
    public function __construct()
    {
        parent::__construct();

        if (OTelDistroExtensionUtil::isLoaded()) {
            throw new TestsInfraException(OTelDistroExtensionUtil::EXTENSION_NAME . ' should NOT be loaded when running unit tests because it will cause a clash');
        }
    }
}
