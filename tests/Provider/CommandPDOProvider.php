<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Tests\Provider\AbstractCommandPDOProvider;

final class CommandPDOProvider extends AbstractCommandPDOProvider
{
    public function bindParam(): array
    {
        $bindParam = parent::bindParam();

        $bindParam[0][6] = [
            'id' => 1,
            'email' => 'user1@example.com',
            'name' => 'user1',
            'address' => 'address1',
            'status' => 1,
            'bool_status' => true,
            'profile_id' => 1,
        ];

        return $bindParam;
    }
}
