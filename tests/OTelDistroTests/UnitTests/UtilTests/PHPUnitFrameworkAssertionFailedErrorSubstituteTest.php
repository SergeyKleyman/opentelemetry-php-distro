<?php

declare(strict_types=1);

namespace OTelDistroTests\UnitTests\UtilTests;

use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\DisableDebugContextTestTrait;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\TestCaseBase;
use OTelDistroTests\Util\TestsRootDir;
use OTelDistroTests\Util\VendorDir;
use Override;
use PhpParser\NodeDumper as PhpParserNodeDumper;
use PhpParser\ParserFactory as PhpParserFactory;
use PHPUnit\Framework\AssertionFailedError;

/**
 * @phpstan-import-type PreProcessMessageCallback from AssertionFailedError
 */
final class PHPUnitFrameworkAssertionFailedErrorSubstituteTest extends TestCaseBase
{
    use DisableDebugContextTestTrait;

    /** @var ?PreProcessMessageCallback */
    private static mixed $preprocessMessageCallbackToRestore;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        self::$preprocessMessageCallbackToRestore = AssertionFailedError::$preprocessMessage;
    }

    #[Override]
    public function tearDown(): void
    {
        AssertionFailedError::$preprocessMessage = self::$preprocessMessageCallbackToRestore;

        parent::tearDown();
    }

    /** @noinspection PhpUnusedParameterInspection */
    public static function testMessageIsPreprocessed(): void
    {
        $textToAdd = ' dummy text added by preprocessMessage';
        $exceptionMsg = null;

        AssertionFailedError::$preprocessMessage = function (AssertionFailedError $exceptionBeingConstructed, string $baseMessage, int $numberOfStackFramesToSkip) use ($textToAdd): string {
            return $baseMessage . $textToAdd;
        };
        try {
            self::fail();
        } catch (AssertionFailedError $ex) {
            $exceptionMsg = $ex->getMessage();
        }
        AssertionFailedError::$preprocessMessage = null;

        self::assertStringContainsString($textToAdd, $exceptionMsg);
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function dataProviderForTestOriginalMatchesVendor(): iterable
    {
        $pathToOriginalSubDir = TestsRootDir::adaptRelativeUnixStylePath('substitutes/PHPUnit_Framework_AssertionFailedError/original');
        $pathToVendorSubDir = VendorDir::adaptRelativeUnixStylePath('phpunit/phpunit/src');

        yield [
            FileUtil::partsToPath($pathToOriginalSubDir, FileUtil::adaptUnixDirectorySeparators('AssertionFailedError.php')),
            FileUtil::partsToPath($pathToVendorSubDir, FileUtil::adaptUnixDirectorySeparators('Framework/Exception/AssertionFailedError.php')),
        ];
    }

    /**
     * @dataProvider dataProviderForTestOriginalMatchesVendor
     */
    public static function testOriginalMatchesVendor(string $pathToOriginalFile, string $pathToVendorFile): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);
        $phpParser = (new PhpParserFactory())->createForHostVersion();
        /**
         * @return array{'PHP': string, 'AST': string}
         */
        $parsePhpAndDumpAst = function (string $pathToPhpFile) use ($phpParser): array {
            $phpFileContent = file_get_contents($pathToPhpFile);
            self::assertNotFalse($phpFileContent);
            $ast = $phpParser->parse($phpFileContent);
            self::assertNotNull($ast);
            $dumper = new PhpParserNodeDumper(['dumpComments' => false, 'dumpPositions' => false]);
            return ['PHP' => $phpFileContent, 'AST' => $dumper->dump($ast)];
        };

        $originalPhpAst = $parsePhpAndDumpAst($pathToOriginalFile);
        $dbgCtx->add(compact('originalPhpAst'));
        $vendorPhpAst = $parsePhpAndDumpAst($pathToVendorFile);
        $dbgCtx->add(compact('vendorPhpAst'));
        self::assertSame($originalPhpAst['AST'], $vendorPhpAst['AST']);
    }
}
