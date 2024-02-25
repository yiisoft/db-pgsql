# PostgreSQL driver for Yii Database Change Log

## 1.2.1 under development

- Bug #316, #6: Support table view constraints (@Tigrov)
- Enh #324: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Enh #303: Support composite types (@Tigrov)
- Enh #330: Create instance of `ArrayParser` directly (@Tigrov)
- Bug #331: Exclude from index column names fields specified in `INCLUDE` clause (@Tigrov)
- Enh #334: Minor `DDLQueryBuilder` refactoring (@Tigrov)
- Enh #338: Add FQNs of functions (@Tigrov)
- Chg #338: Rename composite type to structured type according to SQL standard (@Tigrov)
- Enh #333: Resolve deprecated methods (@Tigrov)

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
