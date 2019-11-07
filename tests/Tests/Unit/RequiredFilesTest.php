<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Tests\Unit;

class RequiredFilesTest extends TestCase
{
    public const FILE = "assertFileExists";

    public const DIRECTORY = "assertDirectoryExists";

    public function getRequiredFiles(): array
    {
        $data = [];
        $box = json_decode(file_get_contents(__DIR__."/../../../box.json"));

        foreach ($box->directories as $directory) {
            $data[] = [$directory, static::DIRECTORY];
        }
        foreach ($box->files as $directory) {
            $data[] = [$directory, static::FILE];
        }

        $data[] = [$box->main, static::FILE];
        $data[] = [".travis/secrets.tar.enc", static::FILE];

        return $data;
    }

    public function testBoxJsonExist(): void
    {
        $this->assertFileExists($appDir = __DIR__."/../../../box.json");
    }

    /**
     * @dataProvider getRequiredFiles
     */
    public function testBoxFileIsPresent($file, $type): void
    {
        $appDir = __DIR__."/../../..";
        $this->$type("{$appDir}/{$file}");
    }
}
