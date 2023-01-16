<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Configuration;

class CmsVersion
{
    /** @var float */
    public $phpVersion;

    /** @var float */
    public $solrVersion;

    /** @var int */
    public $composerVersion;

    /** @var string */
    public $documentRoot;

    /** @var string */
    public $cmsRoot;

    /** @var string */
    public $sessionHandler;

    /** @var string */
    public $consolePath;

    /**
     * @param float $phpVersion
     * @param float $solrVersion
     */
    public function __construct(
        string $phpVersion,
        string $solrVersion,
        int $composerVersion,
        string $documentRoot,
        string $cmsRoot,
        string $sessionHandler,
        string $consolePath
    ) {
        $this->phpVersion = $phpVersion;
        $this->solrVersion = $solrVersion;
        $this->composerVersion = $composerVersion;
        $this->documentRoot = $documentRoot;
        $this->cmsRoot = $cmsRoot;
        $this->sessionHandler = $sessionHandler;
        $this->consolePath = $consolePath;
    }
}
