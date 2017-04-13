<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class RequiredFileTest
 */
class RequiredBoxFilesTest extends TestCase
{

    const FILE = "assertFileExists";

    const DIRECTORY = "assertDirectoryExists";

    /**
     * getRequiredFiles
     */
    public function getRequiredFiles()
    {
        $data = [];
        $box  = json_decode(file_get_contents(__DIR__."/../../../box.json"));

        foreach ($box->directories as $directory) {
            $data[] = [$directory, static::DIRECTORY];
        }
        foreach ($box->files as $directory) {
            $data[] = [$directory, static::FILE];
        }

        $data[] = [$box->main, static::FILE];
        $data[] = [".travis/secrets.tar", static::FILE];

        return $data;
    }

    /**
     *
     */
    public function testBoxJsonExist()
    {
        $this->assertFileExists($appDir = __DIR__."/../../../box.json");
    }

    /**
     * @dataProvider getRequiredFiles
     */
    public function testBoxFileIsPresent($file, $type)
    {
        $appDir = __DIR__."/../../..";
        $this->$type("{$appDir}/{$file}");
    }
}
