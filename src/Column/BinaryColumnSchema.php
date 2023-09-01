<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbStringHelper;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;

use Yiisoft\Db\Schema\SchemaInterface;
use function hex2bin;
use function is_string;
use function str_starts_with;
use function substr;

class BinaryColumnSchema extends AbstractColumnSchema
{
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->type(SchemaInterface::TYPE_BINARY);
        $this->phpType(SchemaInterface::PHP_TYPE_RESOURCE);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return match (true) {
            is_string($value) => new Param($value, PDO::PARAM_LOB),
            $value === null, is_resource($value), $value instanceof ExpressionInterface => $value,
            /** ensure type cast always has . as decimal separator in all locales */
            is_float($value) => DbStringHelper::normalizeFloat($value),
            $value === false => '0',
            default => (string) $value,
        };
    }

    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, '\x')) {
            return hex2bin(substr($value, 2));
        }

        return $value;
    }
}
