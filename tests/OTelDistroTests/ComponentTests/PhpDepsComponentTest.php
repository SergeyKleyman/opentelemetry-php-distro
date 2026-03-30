<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use Closure;
use Composer\Semver\Semver;
use OpenTelemetry\Distro\PhpPartFacade;
use OpenTelemetry\Distro\Util\BoolUtil;
use OTelDistroTests\ComponentTests\Util\AppCodeHostParams;
use OTelDistroTests\ComponentTests\Util\AppCodeTarget;
use OTelDistroTests\ComponentTests\Util\ComponentTestCaseBase;
use OTelDistroTests\ComponentTests\Util\EnvVarUtilForTests;
use OTelDistroTests\ComponentTests\Util\OTelUtil;
use OTelDistroTests\ComponentTests\Util\ProcessUtil;
use OTelDistroTests\ComponentTests\Util\WaitForOTelSignalCounts;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\JsonUtil;
use OTelDistroTests\Util\TimeUtil;
use OTelDistroTests\Util\VendorDir;
use OTelDistroTools\Build\InstallPhpDeps;
use OTelDistroTools\Build\PhpDepsGroup;
use Override;
use PhpParser\Error as PhpParserError;
use PhpParser\Node as PhpParserNode;
use PhpParser\Node\Stmt as PhpParserNodeStmt;
use PhpParser\Node\Stmt\Namespace_ as PhpParserNodeNamespace;
use PhpParser\ErrorHandler\Throwing as PhpParserThrowingErrorHandler;
use PhpParser\NodeTraverser as PhpParserNodeTraverser;
use PhpParser\NodeVisitorAbstract as PhpParserNodeVisitorAbstract;
use PhpParser\ParserFactory;
use SplFileInfo;

/**
 * @group does_not_require_external_services
 */
final class PhpDepsComponentTest extends ComponentTestCaseBase
{
    /**
     * Make sure this value is in sync with the rest of locations where it's defined (see scope_namespace in <repo root>/tools/build/scope_PHP_deps.sh)
     */
    public const SCOPE_NAMESPACE = 'ScopedByElasticOTel';

    private const PROD_VENDOR_DIR_KEY = 'prod_vendor_dir';

    private static function getCurrentPhpVersion(): string
    {
        // PHP_VERSION: 5.3.6-13ubuntu3.2
        // PHP_EXTRA_VERSION: -13ubuntu3.2

        if (PHP_EXTRA_VERSION === '') {
            return PHP_VERSION;
        }

        self::assertStringEndsWith(PHP_EXTRA_VERSION, PHP_VERSION);
        return substr(PHP_VERSION, offset: 0, length: strlen(PHP_VERSION) - strlen(PHP_EXTRA_VERSION));
    }

    private static function getPackagePhpVersionConstraints(string $packageDir): ?string
    {
        $packageComposerJsonFilePath = FileUtil::partsToPath($packageDir, 'composer.json');
        if (!file_exists($packageComposerJsonFilePath)) {
            return null;
        }
        $jsonEncoded = FileUtil::getFileContents($packageComposerJsonFilePath);
        $jsonDecoded = AssertEx::isArray(JsonUtil::decode($jsonEncoded));
        $requireMap = AssertEx::isArray(AssertEx::arrayHasKey('require', $jsonDecoded));
        return AssertEx::isString(AssertEx::arrayHasKey('php', $requireMap));
    }

    /**
     * @param callable(string $packageVendor, string $packageName): void $code
     */
    private static function callForEachPackage(string $prodVendorDir, callable $code): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $dbgCtx->pushSubScope();
        foreach (FileUtil::iterateDirectory($prodVendorDir) as $vendorDirChildEntry) {
            if (!$vendorDirChildEntry->isDir()) {
                continue;
            }
            $dbgCtx->resetTopSubScope(compact('vendorDirChildEntry'));

            $dbgCtx->pushSubScope();
            foreach (FileUtil::iterateDirectory($vendorDirChildEntry->getRealPath()) as $vendorDirGrandChildEntry) {
                if (!$vendorDirGrandChildEntry->isDir()) {
                    continue;
                }
                $dbgCtx->resetTopSubScope(compact('vendorDirGrandChildEntry'));

                $code($vendorDirChildEntry->getBasename(), $vendorDirGrandChildEntry->getBasename());
            }
            $dbgCtx->popSubScope();
        }
        $dbgCtx->popSubScope();
    }

    private static function assertOpcacheEnabled(): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);
        /** @noinspection PhpComposerExtensionStubsInspection */
        $opcacheStatus = AssertEx::isArray(opcache_get_status());
        $dbgCtx->add(compact('opcacheStatus'));
        $opcacheEnabled = AssertEx::isBool(AssertEx::arrayHasKey('opcache_enabled', $opcacheStatus));
        self::assertTrue($opcacheEnabled);
    }

    private static function verifyPackagesPhpVersion(string $prodVendorDir): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);
        $currentPhpVersion = self::getCurrentPhpVersion();
        $dbgCtx->add(compact('currentPhpVersion'));

        self::callForEachPackage(
            $prodVendorDir,
            function (string $packageVendor, string $packageName) use ($prodVendorDir, $dbgCtx, $currentPhpVersion) {
                $packageDir = FileUtil::partsToPath($prodVendorDir, $packageVendor, $packageName);
                if (($phpVersionConstraints = self::getPackagePhpVersionConstraints($packageDir)) === null) {
                    return;
                }

                $packageFqName = "$packageVendor/$packageName";
                $dbgCtx->add(compact('packageFqName'));
                $dbgCtx->add(compact('phpVersionConstraints'));
                if (!Semver::satisfies($currentPhpVersion, $phpVersionConstraints)) {
                    self::fail(
                        'Encountered a package with PHP constraints that are not satisfied by the current PHP version; '
                        . "package: $packageFqName, PHP constraints: $phpVersionConstraints, current PHP version: $currentPhpVersion"
                    );
                }
            }
        );
    }

    private static function containsHiddenDirInPath(string $filePath): bool
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $filePath);
        foreach ($pathParts as $pathPart) {
            if (str_starts_with($pathPart, '.')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param PhpParserNodeStmt[] $statements
     */
    private static function verifyPhpSourceFileNamespace(string $filePath, array $statements): void
    {
        $visitNode = function (PhpParserNode $node) use ($filePath): void {
            $logger = PhpDepsComponentTest::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__)->addAllContext(compact('filePath'));
            $loggerProxy = $logger->ifDebugLevelEnabledNoLine(__FUNCTION__);

            if ($node instanceof PhpParserNodeNamespace) {
                $nameNode = $node->name;
                if ($nameNode === null) {
                    $loggerProxy?->log(__LINE__, '$nameNode === null');
                    return;
                }
                $loggerProxy?->log(__LINE__, '$nameNode->name: ' . $nameNode->name);
                $loggerProxy?->log(__LINE__, '$nameNode->isRelative(): ' . BoolUtil::toString($nameNode->isRelative()));
                // TODO: Sergey Kleyman: UNCOMMENT
                //                Assert::assertStringStartsWith("\\" . PhpDepsTest::SCOPE_NAMESPACE . "\\", $nameNode->name);
            }
        };
        $traverser = new PhpParserNodeTraverser();
        $traverser->addVisitor(
            new class ($visitNode) extends PhpParserNodeVisitorAbstract {
                /**
                 * @param Closure(PhpParserNode): void $visitNodeCode
                 */
                public function __construct(
                    private readonly Closure $visitNodeCode,
                ) {
                }

                /**
                 * @return null
                 */
                #[Override]
                public function enterNode(PhpParserNode $node)
                {
                    ($this->visitNodeCode)($node);
                    return null;
                }
            },
        );

        $traverser->traverse($statements);
    }

    private static function verifyPhpSourceFilesUsingParser(string $prodVendorDir): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $loggerProxy = self::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__)->ifDebugLevelEnabledNoLine(__FUNCTION__);
        $loggerProxy?->log(__LINE__, 'Entered', compact('prodVendorDir'));

        $throwingErrorHandler = new PhpParserThrowingErrorHandler();
        $dbgCtx->pushSubScope();
        foreach (FileUtil::iterateOverFilesInDirectoryRecursively($prodVendorDir) as $fileInfo) {
            $filePath = $fileInfo->getRealPath();
            if ($fileInfo->getExtension() !== 'php' || self::containsHiddenDirInPath($filePath)) {
                continue;
            }

            $dbgCtx->resetTopSubScope(compact('filePath'));
            $loggerProxy?->log(__LINE__, '', compact('filePath'));

            $parser = (new ParserFactory())->createForHostVersion();
            try {
                $statements = $parser->parse(FileUtil::getFileContents($filePath), $throwingErrorHandler);
                self::assertNotNull($statements);
                self::verifyPhpSourceFileNamespace($filePath, $statements);
            } catch (PhpParserError $parserError) {
                $dbgCtx->add(compact('parserError'));
                self::fail("PHP parser failed on $filePath: {$parserError->getMessage()}");
            }
        }
        $dbgCtx->popSubScope();
    }

    private static function verifyPhpSourceFilesUsingOpCache(string $prodVendorDir): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $loggerProxy = self::getLoggerStatic(__NAMESPACE__, __CLASS__, __FILE__)->ifDebugLevelEnabledNoLine(__FUNCTION__);
        $loggerProxy?->log(__LINE__, 'Entered', compact('prodVendorDir'));

        $helperScript = __DIR__ . DIRECTORY_SEPARATOR . 'helperToTestPackagesPhpRequirement.php';
        $helperScriptFileInfo = new SplFileInfo($helperScript);
        $procInfo = ProcessUtil::startProcessAndWaitForItToExit(
            dbgProcessName: $helperScriptFileInfo->getBasename($helperScriptFileInfo->getExtension()),
            command: "php \"$helperScript\" \"$prodVendorDir\"",
            envVars: EnvVarUtilForTests::getAll(),
            maxWaitTimeInMicroseconds: intval(TimeUtil::secondsToMicroseconds(60)) // 1 minute
        );
        $dbgCtx->add(compact('procInfo'));
        self::assertSame(0, $procInfo['exitCode']);
    }

    private static function verifyVendorDevAndProdOnlyPackages(string $prodVendorDir): void
    {
        InstallPhpDeps::verifyDevProdOnlyPackages(PhpDepsGroup::dev, VendorDir::getFullPath());
        InstallPhpDeps::verifyDevProdOnlyPackages(PhpDepsGroup::prod, $prodVendorDir);
    }

    public static function appCodeForTestPackagesHaveCorrectPhpVersion(): void
    {
        OTelUtil::addActiveSpanAttributes([self::PROD_VENDOR_DIR_KEY => PhpPartFacade::getVendorDirPath()]);
    }

    public function testPackagesInVendorForProd(): void
    {
        self::assertOpcacheEnabled();

        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $testCaseHandle = $this->getTestCaseHandle();

        $appCodeHost = $testCaseHandle->ensureMainAppCodeHost(
            function (AppCodeHostParams $appCodeParams): void {
                self::ensureTransactionSpanEnabled($appCodeParams);
            }
        );
        $appCodeHost->execAppCode(AppCodeTarget::asRouted([__CLASS__, 'appCodeForTestPackagesHaveCorrectPhpVersion']));

        $agentBackendComms = $testCaseHandle->waitForEnoughAgentBackendComms(WaitForOTelSignalCounts::spans(1)); // exactly 1 span (the root span) is expected
        $dbgCtx->add(compact('agentBackendComms'));
        $prodVendorDir = FileUtil::normalizePath($agentBackendComms->singleSpan()->attributes->getString(self::PROD_VENDOR_DIR_KEY));

        self::verifyPackagesPhpVersion($prodVendorDir);
        self::verifyPhpSourceFilesUsingParser($prodVendorDir);
        self::verifyPhpSourceFilesUsingOpCache($prodVendorDir);

        self::verifyVendorDevAndProdOnlyPackages($prodVendorDir);
    }
}
