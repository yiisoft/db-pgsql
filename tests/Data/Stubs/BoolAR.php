<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Data\Stubs;

use Yiisoft\ActiveRecord\ActiveRecord;

final class BoolAR extends ActiveRecord
{
    public function tableName(): string
    {
        return 'bool_values';
    }
}
