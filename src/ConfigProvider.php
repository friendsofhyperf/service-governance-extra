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

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                Register\ConsulHealth::class => Register\ConsulHealthFactory::class,
                Lock::class => Lock::class,
            ],
            'listeners' => [
                Listener\RegisterLockListener::class,
            ],
            'signal' => [
                'handlers' => [
                    Handler\DeregisterServicesHandler::class => PHP_INT_MIN,
                ],
            ],
        ];
    }
}
