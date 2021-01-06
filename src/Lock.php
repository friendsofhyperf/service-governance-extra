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

class Lock extends \Swoole\Lock
{
    public function __construct()
    {
        parent::__construct();
    }
}
