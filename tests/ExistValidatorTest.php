<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Pgsql\Tests;

use yii\validators\ExistValidator;

/**
 * @group db
 * @group pgsql
 * @group validators
 */
class ExistValidatorTest extends \Yiisoft\Db\Tests\Validators\ExistValidatorTest
{
    public $driverName = 'pgsql';

    /**
     * @see https://github.com/yiisoft/yii2/issues/14274
     */
    public function testWithCameCasedTableName()
    {
        // The same target table
        $validator = new ExistValidator(['targetAttribute' => 'ref']);
        $model = ValidatorTestRefModel::findOne(['id' => 2]);
        $validator->validateAttribute($model, 'ref');
        $this->assertFalse($model->hasErrors());

        // Different target table
        $validator = new ExistValidator(['targetClass' => ValidatorTestMainModel::class, 'targetAttribute' => 'id']);
        $model = ValidatorTestRefModel::findOne(['id' => 1]);
        $validator->validateAttribute($model, 'ref');
        $this->assertFalse($model->hasErrors());
    }
}

class ValidatorTestRefModel extends \yii\tests\data\validators\models\ValidatorTestRefModel
{
    public static function tableName()
    {
        return 'validatorRef';
    }
}
class ValidatorTestMainModel extends \yii\tests\data\validators\models\ValidatorTestRefModel
{
    public static function tableName()
    {
        return 'validatorMain';
    }
}
