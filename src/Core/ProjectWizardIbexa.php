<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Core;

class ProjectWizardIbexa extends ProjectWizard
{
    public const INIT_IBEXA_OSS = 'ibexa/oss';
    public const INIT_IBEXA_CONTENT = 'ibexa/content';
    public const INIT_IBEXA_COMMERCE = 'ibexa/commerce';
    public const INIT_IBEXA_EXPERIENCE = 'ibexa/experience';

    /**
     * @var array
     */
    protected static $modes = [
        self::INIT_IBEXA_OSS,
        self::INIT_IBEXA_CONTENT,
        self::INIT_IBEXA_COMMERCE,
        self::INIT_IBEXA_EXPERIENCE,
    ];

    public function __invoke(DockerCompose $compose): array
    {
        $this->mode = $this->getInitializationMode();

        $configuration = [
            $this->getNetworkName(),
            $this->getNetworkTCPPort(),
            $this->getSelectedServices(
                $compose->getServices(),
                ['varnish', 'solr', 'adminer', 'redisadmin']
            ),
            $this->getProvisioningFolderName(),
            $this->getComposeFileName(),
        ];

        return $configuration;
    }

    public function getInitializationMode(): string
    {
        $ibexaOss = self::INIT_IBEXA_OSS;
        $ibexaContent = self::INIT_IBEXA_CONTENT;
        $ibexaCommerce = self::INIT_IBEXA_COMMERCE;
        $ibexaExperience = self::INIT_IBEXA_EXPERIENCE;
        $question = <<<END
eZ Launchpad will install a new architecture for you.
 The modes below are available for Ibexa 3.3.*  :
  - <fg=cyan>{$ibexaOss}</>: Installation of Ibexa Open Source see ibexa/oss package.
  - <fg=cyan>{$ibexaContent}</>: Installation of Ibexa Content, it requires a subscription.
  - <fg=cyan>{$ibexaCommerce}</>:  Installation of Ibexa Commerce, it requires a subscription.
  - <fg=cyan>{$ibexaExperience}</>:  Installation of Ibexa Experience, it requires a subscription.
 Please select your <fg=yellow;options=bold>Init</>ialization mode
END;

        return $this->io->choice($question, self::$modes, self::INIT_IBEXA_OSS);
    }

    public function getPackage(): string
    {
        return  $this->mode;
    }
}
