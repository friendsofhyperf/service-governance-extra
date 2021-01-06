<?php

declare(strict_types=1);
/**
 * This file is part of mall-service.
 *
 * @link     https://code.addcn.com/8591/services/mall
 * @document https://code.addcn.com/8591/services/mall/blob/master/README.md
 * @contact  hdj@addcn.com
 */
namespace FriendsOfHyperf\ServiceGovernanceExtra;

class Lock extends \Swoole\Lock
{
    public function __construct()
    {
        parent::__construct();
    }
}
