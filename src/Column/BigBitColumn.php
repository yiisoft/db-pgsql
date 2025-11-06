<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;

use function decbin;
use function gettype;
use function str_pad;

use const STR_PAD_LEFT;

final class BigBitColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = ColumnType::BIT;

    /** @psalm-suppress RedundantCast */
    public function dbTypecast(mixed $value): string|ExpressionInterface|null
    {
        return match (gettype($value)) {
            'integer', 'double' => $this->addZero(decbin((int) $value)),
            'NULL' => null,
            'boolean' => $this->addZero($value ? '1' : '0'),
            'string' => $value === '' ? null : $value,
            default => $value instanceof ExpressionInterface ? $value : (string) $value,
        };
    }

    public function phpTypecast(mixed $value): string|null
    {
        /** @var string|null $value */
        return $value;
    }

    private function addZero(string $value): string
    {
        return str_pad($value, (int) $this->getSize(), '0', STR_PAD_LEFT);
    }
}
