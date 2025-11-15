<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Constant;

final class PgsqlColumnType
{
    public const INT4RANGE = 'int4range';
    public const INT8RANGE = 'int8range';
    public const NUMRANGE = 'numrange';
    public const TSRANGE = 'tsrange';
    public const TSTZRANGE = 'tstzrange';
    public const DATERANGE = 'daterange';
    public const INT4MULTIRANGE = 'int4multirange';
    public const INT8MULTIRANGE = 'int8multirange';
    public const NUMMULTIRANGE = 'nummultirange';
    public const TSMULTIRANGE = 'tsmultirange';
    public const TSTZMULTIRANGE = 'tstzmultirange';
    public const DATEMULTIRANGE = 'datemultirange';
}
