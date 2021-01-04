<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-extra.
 *
 * @link     https://github.com/friendsofhyperf/service-governance-extra
 * @document https://github.com/friendsofhyperf/service-governance-extra/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceExtra\Register;

use Hyperf\Consul\Health;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;

class ConsulHealthFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new Health(function () use ($container) {
            $config = $container->get(ConfigInterface::class);
            $token = $config->get('consul.token', '');
            $options = [
                'timeout' => 2,
                'base_uri' => $config->get('consul.uri', Health::DEFAULT_URI),
            ];

            if (! empty($token)) {
                $options['headers'] = [
                    'X-Consul-Token' => $token,
                ];
            }

            return $container->get(ClientFactory::class)->create($options);
        });
    }
}
