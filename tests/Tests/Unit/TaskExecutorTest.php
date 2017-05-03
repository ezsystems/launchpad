<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class TaskExecutorTest extends TestCase
{
    /**
     * @var TaskExecutor
     */
    protected $executor;

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        parent::setUp();
        $finder = new Finder();
        $files  = $finder->files()->in(__DIR__."/../../../payload/recipes")->name("*.bash");

        $recipes = NovaCollection([]);
        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $recipes->add(str_replace(".bash", "", $file->getFilename()));
        }
        $this->executor = new TaskExecutor($this->getDockerClient(), $this->getConfiguration(), $recipes);
    }

    /**
     *
     */
    public function testComposerInstall()
    {
        $command = "docker-compose -p test -f ".$this->getDockerComposeFilePath();
        $results = $this->executor->composerInstall();
        $this->assertCount(3, $results);

        $suffixes = [];
        foreach ($results as $result) {
            $suffixes[] = trim(str_replace($command, '', $result[0]));
        }

        $startWith = "exec --user www-data engine";
        $path      = $this->getDockerClient()->getProjectPathContainer();

        $this->assertEquals("{$startWith} {$path}/composer_install.bash", $suffixes[0]);
        $this->assertEquals(
            "{$startWith} {$path}/composer.phar config --global http-basic.ez.no login novactive",
            $suffixes[1]
        );
        $this->assertEquals(
            "{$startWith} {$path}/composer.phar config --global http-basic.plopix.net login pass",
            $suffixes[2]
        );
    }
}
