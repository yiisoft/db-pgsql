<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use InvalidArgumentException;
use function array_map;
use function preg_match_all;

final class MultiRangeParser
{
    private const RANGES = [
        Schema::TYPE_INT_MULTIRANGE => Schema::TYPE_INT_RANGE,
        Schema::TYPE_BIGINT_MULTIRANGE => Schema::TYPE_BIGINT_RANGE,
        Schema::TYPE_NUM_MULTIRANGE => Schema::TYPE_NUM_RANGE,
        Schema::TYPE_DATE_MULTIRANGE => Schema::TYPE_DATE_RANGE,
        Schema::TYPE_TS_MULTIRANGE => Schema::TYPE_TS_RANGE,
        Schema::TYPE_TS_TZ_MULTIRANGE => Schema::TYPE_TS_TZ_RANGE,
    ];

    private ?string $type = null;

    public function __construct(?string $type = null)
    {
        $this->type = $type;
    }

    public function withType(?string $type): self
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    public function asMultiInt(): self
    {
        return $this->withType(Schema::TYPE_INT_MULTIRANGE);
    }

    public function asMultiBigInt(): self
    {
        return $this->withType(Schema::TYPE_BIGINT_MULTIRANGE);
    }

    public function asMultiNumeric(): self
    {
        return $this->withType(Schema::TYPE_NUM_MULTIRANGE);
    }

    public function asMultiDate(): self
    {
        return $this->withType(Schema::TYPE_DATE_MULTIRANGE);
    }

    public function asMultiTimestamp(): self
    {
        return $this->withType(Schema::TYPE_TS_MULTIRANGE);
    }

    public function asMultiTimestampTz(): self
    {
        return $this->withType(Schema::TYPE_TS_TZ_MULTIRANGE);
    }

    public function asCustom(): self
    {
        return $this->withType(null);
    }

    public function parse(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value === '{}') {
            return [];
        }

        if (!preg_match_all('/(([\[\(][^,]*,[^\)\]]*[\)\]]),?)+/U', $value, $matches) || $value !== '{' . implode(',', $matches[1]) . '}') {
            throw new InvalidArgumentException('Unsupported range format');
        }

        $type = self::RANGES[$this->type] ?? $this->type;
        $parser = new RangeParser($type);

        return array_map([$parser, 'parse'], $matches[1]);
    }

    public static function isAllowedType(string $type): bool
    {
        return isset(self::RANGES[$type]);
    }
}
