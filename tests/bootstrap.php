<?php

declare(strict_types=1);

if (getenv('ENVIRONMENT', true) === 'local') {
    putenv('YII_PGSQL_DATABASE=yii');
    putenv('YII_PGSQL_HOST=postgres');
    putenv('YII_PGSQL_USER=postgres');
    putenv('YII_PGSQL_PASSWORD=postgres');
}
