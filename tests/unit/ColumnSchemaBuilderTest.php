<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\pgsql\tests;

use yii\db\ColumnSchemaBuilder;

/**
 * ColumnSchemaBuilderTest tests ColumnSchemaBuilder for Oracle.
 *
 * @group db
 * @group pgsql
 */
class ColumnSchemaBuilderTest extends \yii\db\tests\unit\ColumnSchemaBuilderTest
{
    public $driverName = 'pgsql';

    /**
     * @param string $type
     * @param int    $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }
}
