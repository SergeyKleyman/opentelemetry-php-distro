<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

// This script does NOT require classes from vendor on purpose
// because the script is going to load the same files from a different location

/** @var list<string> $argv */
global $argv;
if (count($argv) < 2) {
    echo 'Missing expected command line argument; ' . json_encode(compact('argv')) . PHP_EOL;
    exit(1);
}
$prodVendorDir = $argv[1];

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($prodVendorDir)) as $fileInfo) {
    /** @var SplFileInfo $fileInfo */
    if ($fileInfo->isFile() && ($fileInfo->getExtension() === 'php')) {
        $filePath = $fileInfo->getRealPath();

        $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);
        $containsHiddenDirInPath = false;
        foreach ($pathParts as $pathPart) {
            if (str_starts_with($pathPart, '.')) {
                $containsHiddenDirInPath = true;
                break;
            }
        }
        if ($containsHiddenDirInPath) {
            continue;
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        $retVal = opcache_compile_file($filePath);
        if (!$retVal) {
            echo 'opcache_compile_file() returned false for ' . $filePath . PHP_EOL;
            exit(1);
        }
    }
}
