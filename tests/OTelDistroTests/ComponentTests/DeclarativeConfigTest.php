<?php

declare(strict_types=1);

namespace OTelDistroTests\ComponentTests;

use OTelDistroTests\ComponentTests\Util\AgentBackendComms;
use OTelDistroTests\ComponentTests\Util\AttributesExpectations;
use OTelDistroTests\ComponentTests\Util\ComponentTestCaseBase;
use OTelDistroTests\ComponentTests\Util\EnvVarUtilForTests;
use OTelDistroTests\ComponentTests\Util\HttpServerHandle;
use OTelDistroTests\ComponentTests\Util\TestCaseHandle;
use OTelDistroTests\Util\AssertEx;
use OTelDistroTests\Util\ClassNameUtil;
use OTelDistroTests\Util\Config\OptionForProdName;
use OTelDistroTests\Util\DataProviderForTestBuilder;
use OTelDistroTests\Util\DebugContext;
use OTelDistroTests\Util\DebugContextScopeRef;
use OTelDistroTests\Util\EnvVarUtil;
use OTelDistroTests\Util\FileUtil;
use OTelDistroTests\Util\IterableUtil;
use OTelDistroTests\Util\MixedMap;
use OpenTelemetry\SemConv\Attributes\ServiceAttributes;
use OpenTelemetry\SemConv\Incubating\Attributes\TelemetryIncubatingAttributes;

/**
 * @group does_not_require_external_services
 *
 * @phpstan-import-type EnvVars from EnvVarUtil
 */
final class DeclarativeConfigTest extends ComponentTestCaseBase
{
    private const YAML_TEMPLATE_FILE = __DIR__ . '/TestData/declarative_config_test.yaml';
    private const EXPECTED_SERVICE_NAME = 'declarative-config-component-test';
    private const EXPECTED_CUSTOM_ATTRIBUTE_VALUE = 'test-value-from-yaml';

    ///////////////////////////////////////////////////////////////////////////
    // TODO: Sergey Kleyman: BEGIN: REMOVE: ::
    ///////////////////////////////////////
    private const ENV_VARS_KEY = 'env_vars';
    ///////////////////////////////////////
    // END: REMOVE
    ////////////////////////////////////////////////////////////////////////////

    private const PASS_EXPORTER_OTLP_ENDPOINT_ENV_VAR_TO_APP_CODE_KEY = 'pass_exporter_otlp_endpoint_env_var_to_app_code';
    private const PASS_OTHER_OTEL_ENV_VARS_TO_APP_CODE_KEY = 'pass_other_otel_env_vars_to_app_code';

    private function buildYamlConfigFile(TestCaseHandle $testCaseHandle): string
    {
        /** @noinspection HttpUrlsUsage */
        $endpoint = 'http://' . HttpServerHandle::CLIENT_LOCALHOST_ADDRESS . ':' . $testCaseHandle->getMockOTelCollector()->getPortForAgent();
        $yamlContent = FileUtil::getFileContents(self::YAML_TEMPLATE_FILE);
        $yamlContent = str_replace('${OTEL_EXPORTER_OTLP_ENDPOINT}', $endpoint, $yamlContent);
        $tmpFile = $testCaseHandle->getResourcesCleaner()->getClient()->createTempFile(
            fileNamePrefix: FileUtil::generateTempFileNamePrefix(ClassNameUtil::fqToShortFromRawString(__CLASS__) . '_otel_decl_cfg'),
            fileNameSuffix: '.yaml',
        );
        FileUtil::putFileContents($tmpFile, $yamlContent);
        return $tmpFile;
    }

    ///////////////////////////////////////////////////////////////////////////
    // TODO: Sergey Kleyman: BEGIN: REMOVE: ::
    ///////////////////////////////////////
    /**
     * @return iterable<string, array{MixedMap}>
     */
    public function dataProviderForTestDeclarativeConfigResourceAttributes(): iterable
    {
        return self::adaptDataProviderForTestBuilderToSmokeToDescToMixedMap(
            (new DataProviderForTestBuilder())
                ->addKeyedDimensionAllValuesCombinable(self::PASS_EXPORTER_OTLP_ENDPOINT_ENV_VAR_TO_APP_CODE_KEY, [true, false])
                ->addKeyedDimensionAllValuesCombinable(self::PASS_OTHER_OTEL_ENV_VARS_TO_APP_CODE_KEY, [true, false])
        );
    }
    ///////////////////////////////////////
    // END: REMOVE
    ////////////////////////////////////////////////////////////////////////////

    /**
     * @return array<string, mixed>
     */
    public static function appCodeForTestDeclarativeConfigResourceAttributes(): array
    {
        return [
            self::ENV_VARS_KEY => EnvVarUtilForTests::getAll(),
        ];
    }

    private static function assertExpectedEnvVars(MixedMap $testArgs, MixedMap $appCodeAuxOutput): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        $passExporterOtlpEndpoint = $testArgs->getBool(self::PASS_EXPORTER_OTLP_ENDPOINT_ENV_VAR_TO_APP_CODE_KEY);
        $passOtherOTelEnvVars = $testArgs->getBool(self::PASS_OTHER_OTEL_ENV_VARS_TO_APP_CODE_KEY);
        /** @var EnvVars $appCtxEnvVars */
        $appCtxEnvVars = $appCodeAuxOutput->getArray(self::ENV_VARS_KEY);

        $dbgCtx->pushSubScope();
        foreach ($appCtxEnvVars as $envVarName => $envVarValue) {
            if (($prodOptName = OptionForProdName::tryToFindByEnvVarName($envVarName)) === null) {
                continue;
            }
            $dbgCtx->resetTopSubScope(compact('envVarName', 'envVarValue'));

            $wasPassedCorrectly = match ($prodOptName) {
                // config_file is passed for all the datasets
                OptionForProdName::config_file => true,
                OptionForProdName::exporter_otlp_endpoint => $passExporterOtlpEndpoint,
                default => $passOtherOTelEnvVars,
            };
            self::assertTrue($wasPassedCorrectly);
        }
        $dbgCtx->popSubScope();
    }

    private static function assertForTestDeclarativeConfigResourceAttributes(MixedMap $testArgs, DebugContextScopeRef $dbgCtx, AgentBackendComms $agentBackendComms, MixedMap $appCodeAuxOutput): void
    {
        DebugContext::getCurrentScope(/* out */ $dbgCtx);

        self::assertExpectedEnvVars($testArgs, $appCodeAuxOutput);

        $resources = IterableUtil::toList($agentBackendComms->resources());
        $dbgCtx->add(compact('resources'));
        AssertEx::isPositiveInt(count($resources));

        $resourceAttributesExpectations = new AttributesExpectations(
            attributes: [
                ServiceAttributes::SERVICE_NAME                         => self::EXPECTED_SERVICE_NAME,
                'test.custom.attribute'                                 => self::EXPECTED_CUSTOM_ATTRIBUTE_VALUE,
                TelemetryIncubatingAttributes::TELEMETRY_DISTRO_NAME    => 'opentelemetry-php-distro',
            ],
        );

        foreach ($resources as $resource) {
            $resourceAttributesExpectations->assertMatches($resource->attributes);
        }
    }

    public function testDeclarativeConfigResourceAttributes(MixedMap $testArgs): void
    {
        $testCaseHandle = $this->getTestCaseHandle();
        $yamlConfigFile = $this->buildYamlConfigFile($testCaseHandle);

        $this->runAndEscalateLogLevelOnFailure(
            self::buildDbgDescForTest(__CLASS__, __FUNCTION__),
            function () use ($testArgs, $yamlConfigFile): void {
                self::implTestForAppCodeSetsHowFinished(
                    testArgs: new MixedMap($testArgs->cloneAsArray() + [OptionForProdName::config_file->name => $yamlConfigFile]),
                    subAppCode: [__CLASS__, 'appCodeForTestDeclarativeConfigResourceAttributes'],
                    additionalAssertCode: function (DebugContextScopeRef $dbgCtx, AgentBackendComms $agentBackendComms, MixedMap $appCodeAuxOutput) use ($testArgs): void {
                        self::assertForTestDeclarativeConfigResourceAttributes($testArgs, $dbgCtx, $agentBackendComms, $appCodeAuxOutput);
                    }
                );
            }
        );
    }
}
