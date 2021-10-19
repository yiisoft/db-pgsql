<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use function preg_match;

final class RangeParser
{
    private const RANGES = [
        Schema::TYPE_INT_4_RANGE,
        Schema::TYPE_INT_8_RANGE,
        Schema::TYPE_NUM_RANGE,
        Schema::TYPE_TS_RANGE,
        Schema::TYPE_TS_TZ_RANGE,
        Schema::TYPE_DATE_RANGE,
    ];

    private ?string $type = null;

    public function __construct(?string $type = null)
    {
        if ($type !== null) {
            if (self::isAllowedType($type)) {
                $this->type = $type;
            } else {
                throw new InvalidArgumentException('Unsupported range type "' . $type . '"');
            }
        }
    }

    public function parse(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!preg_match('/^(\[|\()([^,]*),([^\)\]]*)(\)|\])$/', $value, $matches)) {
            throw new InvalidArgumentException();
        }

        $lower = $matches[2] ? trim($matches[2], '"') : null;
        $upper = $matches[3] ? trim($matches[3], '"') : null;
        $includeLower = $matches[1] === '[';
        $includeUpper = $matches[4] === ']';

        if ($lower === null && $upper === null) {
            return [null, null];
        }

        $type = $this->type ?? self::parseType($lower, $upper);

        switch ($type) {
            case Schema::TYPE_INT_4_RANGE:
            case Schema::TYPE_INT_8_RANGE:
                return self::parseIntRange($lower, $upper, $includeLower, $includeUpper);
            case Schema::TYPE_NUM_RANGE:
                return self::parseNumRange($lower, $upper, $includeLower, $includeUpper);
            case Schema::TYPE_DATE_RANGE:
                return self::parseDateRange($lower, $upper, $includeLower, $includeUpper);
            case Schema::TYPE_TS_RANGE:
                return self::parseTsRange($lower, $upper, $includeLower, $includeUpper);
            case Schema::TYPE_TS_TZ_RANGE:
                return self::parseTsTzRange($lower, $upper, $includeLower, $includeUpper);
            default:
                throw new RuntimeException();
        }
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

    private static function parseNumRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
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

    private static function parseTsRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
    {
        $min = $lower ? DateTime::createFromFormat('Y-m-d H:i:s', $lower) : null;
        $max = $upper ? DateTime::createFromFormat('Y-m-d H:i:s', $upper) : null;

        return [$min, $max];
    }

    private static function parseTsTzRange(?string $lower, ?string $upper, bool $includeLower, bool $includeUpper): array
    {
        $min = $lower ? DateTime::createFromFormat('Y-m-d H:i:sP', $lower) : null;
        $max = $upper ? DateTime::createFromFormat('Y-m-d H:i:sP', $upper) : null;

        return [$min, $max];
    }

    public static function isAllowedType(string $type): bool
    {
        return in_array($type, self::RANGES, true);
    }

    /**
     * Find range type from value format
     *
     * @param string $lower
     * @param string $upper
     *
     * @return string|null
     */
    private static function parseType(?string $lower, ?string $upper): ?string
    {
        if ($lower !== null && $upper !== null) {
            if (filter_var($lower, FILTER_VALIDATE_INT) && filter_var($upper, FILTER_VALIDATE_INT)) {
                return Schema::TYPE_INT_4_RANGE;
            }

            if (filter_var($lower, FILTER_VALIDATE_FLOAT) && filter_var($upper, FILTER_VALIDATE_FLOAT)) {
                return Schema::TYPE_NUM_RANGE;
            }
        }

        $value = $lower ?? $upper;

        if (filter_var($value, FILTER_VALIDATE_INT)) {
            return Schema::TYPE_INT_4_RANGE;
        }


        if (filter_var($value, FILTER_VALIDATE_FLOAT)) {
            return Schema::TYPE_NUM_RANGE;
        }

        if (DateTime::createFromFormat('Y-m-d', $value)) {
            return Schema::TYPE_DATE_RANGE;
        }

        if (DateTime::createFromFormat('Y-m-d H:i:s', $value)) {
            return Schema::TYPE_TS_RANGE;
        }

        if (DateTime::createFromFormat('Y-m-d H:i:sP', $value)) {
            return Schema::TYPE_TS_TZ_RANGE;
        }

        return null;
    }
}
