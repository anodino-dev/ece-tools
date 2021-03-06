<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Functional\Acceptance;

use Magento\MagentoCloud\Test\Functional\Codeception\Docker;

/**
 * Tests extensibility base deployment scenarios
 */
class ScenarioExtensibilityCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function _before(\CliTester $I)
    {
        parent::_before($I);
        $I->cloneTemplate();
        $I->addEceComposerRepo();
        $I->addEceExtendComposerRepo();
        $I->uploadToContainer('files/debug_logging/.magento.env.yaml', '/.magento.env.yaml', Docker::BUILD_CONTAINER);
    }

    /**
     * @param \CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testScenarioExtensibilityAndPriority(\CliTester $I)
    {
        $generateScenarios = [
            './vendor/magento/ece-tools/scenario/build/generate.xml',
            './vendor/magento/ece-tools-extend/scenario/extend-build-generate.xml',
            './vendor/magento/ece-tools-extend/scenario/extend-build-generate-skip-di.xml',
        ];
        $transferScenarios = [
            './vendor/magento/ece-tools/scenario/build/transfer.xml',
            './vendor/magento/ece-tools-extend/scenario/extend-build-transfer.xml',
        ];

        $I->assertTrue(
            $I->runEceToolsCommand(sprintf('run %s', implode(' ', $generateScenarios)), Docker::BUILD_CONTAINER)
        );
        $I->assertTrue(
            $I->runEceToolsCommand(sprintf('run %s', implode(' ', $transferScenarios)), Docker::BUILD_CONTAINER)
        );
        $I->startEnvironment();

        $cloudLog = $I->grabFileContent('/var/log/cloud.log', Docker::BUILD_CONTAINER);

        $I->assertContains(
            'Step "copy-sample-data" was skipped',
            $cloudLog,
            'Checks that step copy-sample-data was skipped'
        );
        $I->assertContains('Step "compile-di" was skipped', $cloudLog, 'Checks that step compile-di was skipped');
        $I->assertContains(
            'Doing some actions after static content generation',
            $cloudLog,
            'Checks that new step update-static-content was added'
        );
        $I->assertContains(
            'Customized step for enabling production mode',
            $cloudLog,
            'Checks that step set-production-mode was customized'
        );

        $cloudLog = str_replace(PHP_EOL, " ", $cloudLog);
        $I->assertRegExp(
            '/(deploy-static-content).*?(update-static-content)/i',
            $cloudLog,
            'Checks that update-static-content step was run after deploy-static-content'
        );
        $I->assertRegExp(
            '/(clear-init-directory).*?(compress-static-content)/i',
            $cloudLog,
            'Checks that priority for step clear-init-directory and compress-static-content was swapped'
        );
        $I->assertNotRegExp(
            '/(compress-static-content).*?(clear-init-directory)/i',
            $cloudLog,
            'Checks that priority for step clear-init-directory and compress-static-content is different then in '
            . ' default scenario'
        );
    }
}
