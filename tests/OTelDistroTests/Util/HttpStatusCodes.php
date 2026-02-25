<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use Fig\Http\Message\StatusCodeInterface;

final class HttpStatusCodes
{
    public const OK = StatusCodeInterface::STATUS_OK;
    public const BAD_REQUEST = StatusCodeInterface::STATUS_BAD_REQUEST;
    public const INTERNAL_SERVER_ERROR = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
}
