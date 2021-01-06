<?php

declare(strict_types=1);
/**
 * This file is part of service-governance-extra.
 *
 * @link     https://github.com/friendsofhyperf/service-governance-extra
 * @document https://github.com/friendsofhyperf/service-governance-extra/blob/main/README.md
 * @contact  huangdijia@gmail.com
 */
namespace FriendsOfHyperf\ServiceGovernanceExtra\Handler;

use FriendsOfHyperf\ServiceGovernanceExtra\Lock;
use FriendsOfHyperf\ServiceGovernanceExtra\Register\ConsulHealth;
use Hyperf\Consul\Exception\ServerException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ServiceGovernance\Register\ConsulAgent;
use Hyperf\ServiceGovernance\ServiceManager;
use Hyperf\Signal\SignalHandlerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

class DeregisterServicesHandler implements SignalHandlerInterface
{
    /**
     * @var ConsulAgent
     */
    protected $consulAgent;

    /**
     * @var ConsulHealth
     */
    protected $consulHealth;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Lock
     */
    protected $lock;

    /**
     * @var array
     */
    protected $defaultLoggerContext = [
        'component' => 'service-governance-extra',
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(ConfigInterface::class);
        $this->consulAgent = $container->get(ConsulAgent::class);
        $this->consulHealth = $container->get(ConsulHealth::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->serviceManager = $container->get(ServiceManager::class);
        $this->lock = $container->get(Lock::class);
    }

    public function listen(): array
    {
        return [
            [self::WORKER, SIGTERM],
            [self::WORKER, SIGINT],
        ];
    }

    public function handle(int $signal): void
    {
        if ($signal !== SIGINT) {
            $seconds = (int) $this->config->get('server.settings.max_wait_time', 3);
            sleep($seconds);
        }

        if (! $this->lock->trylock()) {
            return;
        }

        try {
            $services = $this->serviceManager->all();
            $servers = $this->getServers();
            $callables = [];

            foreach ($services as $serviceName => $serviceProtocols) {
                foreach ($serviceProtocols as $paths) {
                    foreach ($paths as $path => $service) {
                        if (! isset($service['publishTo'], $service['server'])) {
                            continue;
                        }

                        [$address, $port] = $servers[$service['server']];

                        switch ($service['publishTo']) {
                        case 'consul':
                            $callables[$serviceName] = function () use ($serviceName, $address, $port) {
                                $this->deregisterConsul($serviceName, $address, $port);
                            };

                            break;
                    }
                    }
                }
            }

            if (count($callables)) {
                parallel($callables);
            }
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $this->lock->unlock();
        }
    }

    /**
     * Deregister.
     * @throws ServerException
     */
    protected function deregisterConsul(string $serviceName, string $address, int $port)
    {
        collect($this->consulHealth->service($serviceName)->json())
            ->filter(function ($item) use ($address, $port) {
                return $address == $item['Service']['Address'] && $port == $item['Service']['Port'];
            })
            ->transform(function ($item) {
                return $item['Service']['ID'];
            })
            ->unique()
            ->each(function ($serviceId) {
                $this->consulAgent->deregisterService($serviceId);
                $this->logger->info(sprintf('Service %s deregistered.', $serviceId), $this->defaultLoggerContext);
            });
    }

    /**
     * Get Servers.
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function getServers(): array
    {
        $result = [];
        $servers = $this->config->get('server.servers', []);

        foreach ($servers as $server) {
            if (! isset($server['name'], $server['host'], $server['port'])) {
                continue;
            }

            if (! $server['name']) {
                throw new \InvalidArgumentException('Invalid server name');
            }

            $host = $server['host'];

            if (in_array($host, ['0.0.0.0', 'localhost'])) {
                $host = $this->getInternalIp();
            }

            if (! filter_var($host, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException(sprintf('Invalid host %s', $host));
            }

            $port = $server['port'];

            if (! is_numeric($port) || ($port < 0 || $port > 65535)) {
                throw new \InvalidArgumentException(sprintf('Invalid port %s', $port));
            }

            $port = (int) $port;
            $result[$server['name']] = [$host, $port];
        }

        return $result;
    }

    /**
     * Get Internal IP.
     * @throws RuntimeException
     */
    protected function getInternalIp(): string
    {
        $ips = swoole_get_local_ip();
        if (is_array($ips) && ! empty($ips)) {
            return current($ips);
        }
        /** @var mixed|string $ip */
        $ip = gethostbyname(gethostname());
        if (is_string($ip)) {
            return $ip;
        }
        throw new \RuntimeException('Can not get the internal IP.');
    }
}
