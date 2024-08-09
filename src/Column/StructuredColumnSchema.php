<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Traversable;
use Yiisoft\Db\Constant\PhpType;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Pgsql\StructuredExpression;
use Yiisoft\Db\Pgsql\StructuredParser;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function array_keys;
use function is_iterable;
use function is_string;
use function iterator_to_array;

final class StructuredColumnSchema extends AbstractColumnSchema implements StructuredColumnSchemaInterface
{
    /**
     * @var ColumnSchemaInterface[] Columns metadata of the composite type.
     * @psalm-var array<string, ColumnSchemaInterface>
     */
    private array $columns = [];

    public function __construct(
        string $type = Schema::TYPE_STRUCTURED,
    ) {
        parent::__construct($type);
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPhpType(): string
    {
        return PhpType::ARRAY;
    }

    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        return new StructuredExpression($value, $this->getDbType(), $this->columns);
    }

    public function phpTypecast(mixed $value): array|null
    {
        if (is_string($value)) {
            $value = (new StructuredParser())->parse($value);
        }

        if (!is_iterable($value)) {
            return null;
        }

        if (empty($this->columns)) {
            return $value instanceof Traversable
                ? iterator_to_array($value)
                : $value;
        }

        $fields = [];
        $columnNames = array_keys($this->columns);

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            $columnName = $columnNames[$columnName] ?? $columnName;

            if (isset($this->columns[$columnName])) {
                $fields[$columnName] = $this->columns[$columnName]->phpTypecast($item);
            } else {
                $fields[$columnName] = $item;
            }
        }

        return $fields;
    }
}
