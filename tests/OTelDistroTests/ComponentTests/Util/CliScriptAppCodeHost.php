<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests\Util;

use Override;

final class CliScriptAppCodeHost extends AppCodeHostBase
{
    public const SCRIPT_TO_RUN_APP_CODE_HOST = 'runCliScriptAppCodeHost.php';

    #[Override]
    protected function runImpl(): void
    {
        $this->callAppCode();
    }
}
