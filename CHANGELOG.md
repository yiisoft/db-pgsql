# PostgreSQL driver for Yii Database Change Log

## 1.1.1 under development

- Enh #302: Refactor `ColumnSchema` (@Tigrov)
- Bug #302: Fix incorrect convert string value for BIT type (@Tigrov)

## 1.1.0 July 24, 2023

- Chg #288: Typecast refactoring (@Tigrov)
- Chg #291: Update phpTypecast for bool type (@Tigrov)
- Enh #282: Support `numeric` arrays, improve support of domain types and `int` and `varchar` array types (@Tigrov)
- Enh #284: Add tests for `binary` type and fix casting of default value (@Tigrov)
- Enh #289: Array parser refactoring (@Tigrov)
- Enh #294: Refactoring of `Schema::normalizeDefaultValue()` method (@Tigrov)
- Bug #287: Fix `bit` type (@Tigrov)
- Bug #295: Fix multiline and single quote in default string value, add support for PostgreSQL 9.4 parentheses around negative numeric default values (@Tigrov)
- Bug #296: Prevent posible issues with array default values `('{one,two}'::text[])::varchar[]`, remove `ArrayParser::parseString()` (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
