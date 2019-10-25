<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class BehatTest.
 */
class BehatTest extends TestCase
{
    /**
     * Run Behat - trick method to get Code Coverage on Behat.
     *
     * @group behat
     *
     * @return bool|int
     */
    public function testThatBehatScenariosMeetAcceptanceCriteria()
    {
        try {
            $factory      = new \Behat\Behat\ApplicationFactory();
            $bApplication = $factory->createApplication();
            $input        = new ArrayInput(['--format' => ['progress'], '--config' => __DIR__.'/../../behat.yml']);
            $output       = new ConsoleOutput();
            $bApplication->setAutoExit(false);
            $result = $bApplication->run($input, $output);
            $this->assertEquals(0, $result);
        } catch (Exception $exception) {
            $this->fail($exception->getMessage());
        }

        return true;
    }
}
