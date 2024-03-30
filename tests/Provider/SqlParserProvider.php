<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

class SqlParserProvider extends \Yiisoft\Db\Tests\Provider\SqlParserProvider
{
    public static function getNextPlaceholder(): array
    {
        return [
            ...parent::getNextPlaceholder(),
            [
                "name = e':name' AND age = :age",
                ':age',
                26,
            ],
            [
                "name = E':name' AND age = :age",
                ':age',
                26,
            ],
            [
                "name = E':name' AND age = :age",
                ':age',
                26,
            ],
            [
                'name = $$:name$$ AND age = :age',
                ':age',
                27,
            ],
            [
                'name = $q$:name$q$ AND age = :age',
                ':age',
                29,
            ],
            [
                'name = $name AND age = :age',
                ':age',
                23,
            ],
            [
                'name = name$1$ AND age = :age',
                ':age',
                25,
            ],
        ];
    }
}
