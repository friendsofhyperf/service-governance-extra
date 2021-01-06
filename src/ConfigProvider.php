<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-extra.
 *
 * @link     https://github.com/friendsofhyperf/service-governance-extra
 * @document https://github.com/friendsofhyperf/service-governance-extra/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceExtra;

use FriendsOfHyperf\ServiceGovernanceExtra\Handler\DeregisterServicesHandler;
use FriendsOfHyperf\ServiceGovernanceExtra\Register\ConsulHealth;
use FriendsOfHyperf\ServiceGovernanceExtra\Register\ConsulHealthFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConsulHealth::class => ConsulHealthFactory::class,
            ],
            'signal' => [
                'handlers' => [
                    DeregisterServicesHandler::class => PHP_INT_MIN,
                ],
            ],
        ];
    }
}
