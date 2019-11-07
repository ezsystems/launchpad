<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Behat\Commands;

use Behat\Behat\Context\Context as BaseContext;
use Exception;
use eZ\Launchpad\Console\ApplicationFactory;
use Symfony\Component\Console\Tester\ApplicationTester;
use Behat\Gherkin\Node\TableNode;

class Context implements BaseContext
{
    /**
     * @var ApplicationTester
     */
    protected $tester;

    public function __construct()
    {
        $this->tester = new ApplicationTester(ApplicationFactory::create(false, 'test', "Linux"));
    }

    /**
     * @When /^I run "([^"]*)" command$/
     */
    public function iRunCommand($name, $params = []): void
    {
        $parameters = ['command' => $name];

        $parameters += $params;
        $this->tester->run($parameters);
    }

    /**
     * @Then /^I should see "([^"]*)"$/
     */
    public function iShouldSee($value): void
    {
        $output = $this->tester->getDisplay();
        if (preg_match('/(.*) OR (.*)/us', $value)) {
            preg_match_all('/(.*) OR (.*)/us', $value, $matches);
            if ((strpos($output, $matches[1][0]) === false) && (strpos($output, $matches[2][0]) === false)) {
                throw new Exception(
                    sprintf('Did not see either "%s" OR "%s" in output "%s"', $matches[1][0], $matches[2][0], $output)
                );
            }
        } else {
            if (strpos($output, $value) === false) {
                throw new Exception(sprintf('Did not see "%s" in output "%s"', $value, $output));
            }
        }
    }

    /**
     * @When /^I run "([^"]*)" command with parameter "([^"]*)": "([^"]*)"$/
     */
    public function iRunCommandWithParameter($name, $paramName, $params): void
    {
        $this->iRunCommand($name, [$paramName => explode(' ', $params)]);
    }

    /**
     * @Then /^I should see:$/
     */
    public function iShouldSee1(TableNode $table): void
    {
        foreach ($table->getRows() as $value) {
            $this->iShouldSee($value[0]);
        }
    }
}
