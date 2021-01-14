<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-extra.
 *
 * @link     https://github.com/friendsofhyperf/service-governance-extra
 * @document https://github.com/friendsofhyperf/service-governance-extra/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceExtra\Aspect;

use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

class RegisterServiceAspect extends AbstractAspect
{
    public $classes = [
        'Hyperf\ServiceGovernance\Listener\RegisterServiceListener::getServers',
        'FriendsOfHyperf\ServiceGovernanceExtra\Handler\DeregisterServicesHandler::getServers',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $servers = $proceedingJoinPoint->process();

        if (env('HOST_MACHINE_ADDR')) {
            foreach ($servers as [&$host, $port]) {
                $host = env('HOST_MACHINE_ADDR');
            }
        }

        return $servers;
    }
}
