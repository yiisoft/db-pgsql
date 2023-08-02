<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use function preg_match;

final class RangeParser
{
    private const RANGES = [
        Schema::TYPE_INT_RANGE,
        Schema::TYPE_BIGINT_RANGE,
        Schema::TYPE_NUM_RANGE,
        Schema::TYPE_TS_RANGE,
        Schema::TYPE_TS_TZ_RANGE,
        Schema::TYPE_DATE_RANGE,
    ];

    private ?string $type;

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

    public function asInt(): self
    {
        return $this->withType(Schema::TYPE_INT_RANGE);
    }

    public function asBigInt(): self
    {
        return $this->withType(Schema::TYPE_BIGINT_RANGE);
    }

    public function asNumeric(): self
    {
        return $this->withType(Schema::TYPE_NUM_RANGE);
    }

    public function asDate(): self
    {
        return $this->withType(Schema::TYPE_DATE_RANGE);
    }

    public function asTimestamp(): self
    {
        return $this->withType(Schema::TYPE_TS_RANGE);
    }

    public function asTimestampTz(): self
    {
        return $this->withType(Schema::TYPE_TS_TZ_RANGE);
    }

    public function asCustom(): self
    {
        return $this->withType(null);
    }

    public function parse(?string $value): ?array
    {
        if ($value === null || $value === 'empty') {
            return null;
        }

        if (!preg_match('/^(?P<open>\[|\()(?P<lower>[^,]*),(?P<upper>[^\)\]]*)(?P<close>\)|\])$/', $value, $matches)) {
            throw new InvalidArgumentException('Unsupported range format');
        }

        $lower = $matches['lower'] ? trim($matches['lower'], '"') : null;
        $upper = $matches['upper'] ? trim($matches['upper'], '"') : null;
        $includeLower = $matches['open'] === '[';
        $includeUpper = $matches['close'] === ']';

        if ($lower === null && $upper === null) {
            return [null, null];
        }

        return match($this->type) {
            Schema::TYPE_INT_RANGE => self::parseIntRange($lower, $upper, $includeLower, $includeUpper),
            Schema::TYPE_BIGINT_RANGE => self::parseBigIntRange($lower, $upper, $includeLower, $includeUpper),
            Schema::TYPE_NUM_RANGE => self::parseNumRange($lower, $upper),
            Schema::TYPE_DATE_RANGE => self::parseDateRange($lower, $upper, $includeLower, $includeUpper),
            Schema::TYPE_TS_RANGE => self::parseTsRange($lower, $upper),
            Schema::TYPE_TS_TZ_RANGE => self::parseTsTzRange($lower, $upper),
            default => [$lower, $upper]
        };
    }

    private static function parseIntRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
    {
        $min = $lower === null ? null : (int) $lower;
        $max = $upper === null ? null : (int) $upper;

        if ($min !== null && $includeLower === false) {
            $min += 1;
        }

        if ($max !== null && $includeUpper === false) {
            $max -= 1;
        }

        return [$min, $max];
    }

    private static function parseBigIntRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
    {
        if (PHP_INT_SIZE === 8) {
            return self::parseIntRange($lower, $upper, $includeLower, $includeUpper);
        }

        [$min, $max] = self::parseNumRange($lower, $upper);

        if ($min !== null && $includeLower === false) {
            /** @var float $min */
            $min += 1;
        }

        if ($max !== null && $includeUpper === false) {
            /** @var float $max */
            $max -= 1;
        }

        return [$min, $max];
    }

    private static function parseNumRange(?string $lower, ?string $upper): array
    {
        $min = $lower === null ? null : (float) $lower;
        $max = $upper === null ? null : (float) $upper;

        return [$min, $max];
    }

    private static function parseDateRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
    {
        $interval = new DateInterval('P1D');
        $min = $lower ? DateTime::createFromFormat('Y-m-d', $lower) : null;
        $max = $upper ? DateTime::createFromFormat('Y-m-d', $upper) : null;

        if ($min && $includeLower === false) {
            $min->add($interval);
        }

        if ($max && $includeUpper === false) {
            $max->sub($interval);
        }

        return [$min, $max];
    }

    private static function parseTsRange(?string $lower, ?string $upper): array
    {
        $min = $lower ? DateTime::createFromFormat('Y-m-d H:i:s', $lower) : null;
        $max = $upper ? DateTime::createFromFormat('Y-m-d H:i:s', $upper) : null;

        return [$min, $max];
    }

    private static function parseTsTzRange(?string $lower, ?string $upper): array
    {
        $min = $lower ? DateTime::createFromFormat('Y-m-d H:i:sP', $lower) : null;
        $max = $upper ? DateTime::createFromFormat('Y-m-d H:i:sP', $upper) : null;

        return [$min, $max];
    }

    public static function isAllowedType(string $type): bool
    {
        return in_array($type, self::RANGES, true);
    }
}
