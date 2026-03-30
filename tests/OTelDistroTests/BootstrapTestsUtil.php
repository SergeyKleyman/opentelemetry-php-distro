<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace OTelDistroTests;

use OTelDistroTests\Util\RepoRootDir;
use OTelDistroTests\Util\ExceptionUtil;
use OTelDistroTools\BootstrapToolsUtil;

final class BootstrapTestsUtil
{
    public static function loadTestContextClasses(bool $autoloadProd): void
    {
        require __DIR__ . '/../../tools/bootstrap_shared.php';

        if ($autoloadProd) {
            BootstrapToolsUtil::requireComposerAutoload(__DIR__ . "/../../vendor_prod/autoload.php");
        }

        BootstrapToolsUtil::requireComposerAutoload(__DIR__ . "/../../vendor/autoload.php");
        // Substitutes should be loaded IMMEDIATELY AFTER vendor autoload
        require __DIR__ . '/../substitutes/load.php';

        ExceptionUtil::runCatchLogRethrow(
            function (): void {
                RepoRootDir::setFullPath(__DIR__ . '/../..');

                require __DIR__ . '/../polyfills/load.php';
                require __DIR__ . '/../otel_distro_extension_stubs/load.php';
                require __DIR__ . '/../dummyFuncForTestsWithoutNamespace.php';
                require __DIR__ . '/dummyFuncForTestsWithNamespace.php';
            },
        );
    }
}
