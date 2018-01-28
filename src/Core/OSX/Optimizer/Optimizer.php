<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core\OSX\Optimizer;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;

abstract class Optimizer
{
    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    /**
     * ApplicationUpdate constructor.
     *
     * @param $configuration
     */
    public function __construct(ProjectConfiguration $configuration)
    {
        $this->projectConfiguration = $configuration;
    }
}
