# PostgreSQL driver for Yii Database Change Log

## 2.0.0 under development

- Enh #336: Implement `SqlParser` and `ExpressionBuilder` driver classes (@Tigrov)
- New #315: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Chg #348: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Enh #349: Add method chaining for column classes (@Tigrov)
- New #350: Add array overlaps and JSON overlaps condition builders (@Tigrov)
- Enh #353: Update `bit` type according to main PR yiisoft/db#860 (@Tigrov) 
- Enh #354: Refactor PHP type of `ColumnSchemaInterface` instances (@Tigrov)
- Enh #356: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- New #355, #368, #370, #399: Implement `ColumnFactory` class (@Tigrov)
- Enh #359: Separate column type constants (@Tigrov)
- Enh #359: Remove `Schema::TYPE_ARRAY` and `Schema::TYPE_STRUCTURED` constants (@Tigrov)
- New #360: Realize `ColumnBuilder` class (@Tigrov)
- Enh #362: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #364, #372: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Enh #365: Refactor `Dsn` class (@Tigrov)
- Enh #366: Use constructor to create columns and initialize properties (@Tigrov)
- Enh #370: Refactor `Schema::normalizeDefaultValue()` method and move it to `ColumnFactory` class (@Tigrov)
- New #373: Override `QueryBuilder::prepareBinary()` method (@Tigrov)
- Chg #375: Update `QueryBuilder` constructor (@Tigrov)
- Enh #374: Use `ColumnDefinitionBuilder` to generate table column SQL representation (@Tigrov)
- Enh #378: Improve loading schemas of views (@Tigrov)
- Enh #379: Remove `ColumnInterface` (@Tigrov)
- Enh #380: Rename `ColumnSchemaInterface` to `ColumnInterface` (@Tigrov)
- Enh #381, #383: Add `ColumnDefinitionParser` class (@Tigrov)
- Enh #382: Replace `DbArrayHelper::getColumn()` with `array_column()` (@Tigrov)
- New #384: Add `IndexMethod` class (@Tigrov)
- Bug #387: Explicitly mark nullable parameters (@vjik)
- Enh #386: Refactor array, structured and JSON expression builders (@Tigrov)
- Chg #388: Change supported PHP versions to `8.1 - 8.4` (@Tigrov)
- Enh #388: Minor refactoring (@Tigrov)
- Chg #390: Remove `yiisoft/json` dependency (@Tigrov)
- Enh #393: Refactor according changes in `db` package (@Tigrov)
- New #391: Add `caseSensitive` option to like condition (@vjik)
- Enh #396: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)

## 1.3.0 March 21, 2024

- Enh #303, #338: Support structured type (@Tigrov)
- Enh #324: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Enh #330: Create instance of `ArrayParser` directly (@Tigrov)
- Enh #333: Resolve deprecated methods (@Tigrov)
- Enh #334: Minor `DDLQueryBuilder` refactoring (@Tigrov)
- Bug #316, #6: Support table view constraints (@Tigrov)
- Bug #331: Exclude from index column names fields specified in `INCLUDE` clause (@Tigrov)

## 1.2.0 November 12, 2023

- Chg #319: Remove use of abstract type `SchemaInterface::TYPE_JSONB` (@Tigrov)
- Enh #300: Refactor `ArrayExpressionBuilder` (@Tigrov)
- Enh #301: Refactor `JsonExpressionBuilder` (@Tigrov)
- Enh #302: Refactor `ColumnSchema` (@Tigrov)
- Enh #321: Move methods from `Command` to `AbstractPdoCommand` class (@Tigrov)
- Bug #302: Fix incorrect convert string value for BIT type (@Tigrov)
- Bug #309: Fix retrieving sequence name from default value (@Tigrov)
- Bug #313: Refactor `DMLQueryBuilder`, related with yiisoft/db#746 (@Tigrov)

## 1.1.0 July 24, 2023

- Chg #288: Typecast refactoring (@Tigrov)
- Chg #291: Update phpTypecast for bool type (@Tigrov)
- Enh #282: Support `numeric` arrays, improve support of domain types and `int` and `varchar` array types (@Tigrov)
- Enh #284: Add tests for `binary` type and fix casting of default value (@Tigrov)
- Enh #289: Array parser refactoring (@Tigrov)
- Enh #294: Refactoring of `Schema::normalizeDefaultValue()` method (@Tigrov)
- Bug #287: Fix `bit` type (@Tigrov)
- Bug #295: Fix multiline and single quote in default string value, add support for PostgreSQL 9.4 parentheses around negative numeric default values (@Tigrov)
- Bug #296: Prevent possible issues with array default values `('{one,two}'::text[])::varchar[]`, remove `ArrayParser::parseString()` (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
