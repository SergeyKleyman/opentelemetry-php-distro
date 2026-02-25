<?php

declare(strict_types=1);

namespace OTelDistroTests\Util;

use Fig\Http\Message\RequestMethodInterface;

final class HttpMethods
{
    public const GET = RequestMethodInterface::METHOD_GET;
    public const POST = RequestMethodInterface::METHOD_POST;
    public const DELETE = RequestMethodInterface::METHOD_DELETE;
}
