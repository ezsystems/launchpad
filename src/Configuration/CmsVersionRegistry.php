<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Configuration;

class CmsVersionRegistry
{
    /** @var CmsVersion */
    protected $versions;

    public function __construct(array $config = [])
    {
        foreach ($config as $versionNumber => $versionConfig) {
            $this->addVersion(
                $versionNumber,
                new CmsVersion(
                    $versionConfig['php_version'],
                    $versionConfig['solr_version'],
                    $versionConfig['composer_version'],
                    $versionConfig['document_root'],
                    $versionConfig['cms_root'],
                    $versionConfig['session_handler'],
                    $versionConfig['console_path'],
                )
            );
        }
    }

    public function addVersion(string $versionNumber, CmsVersion $cmsVersion)
    {
        $this->versions[$versionNumber] = $cmsVersion;
    }

    public function getVersion(string $versionNumber): CmsVersion
    {
        return $this->versions[$versionNumber];
    }
}
