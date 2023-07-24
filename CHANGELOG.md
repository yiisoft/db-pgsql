# PostgreSQL driver for Yii Database Change Log

## 1.0.1 under development

- Enh #282: Support `numeric` arrays, improve support of domain types and `int` and `varchar` array types (@Tigrov)
- Enh #284: Add tests for `binary` type and fix casting of default value (@Tigrov)
- Bug #287: Fix `bit` type (@Tigrov)
- Enh #289: Array parser refactoring (@Tigrov)
- Chg #288: Typecast refactoring (@Tigrov)
- Chg #291: Update phpTypecast for bool type (@Tigrov)
- Enh #294: Refactoring of `Schema::normalizeDefaultValue()` method (@Tigrov)
- Bug #296: Prevent posible issues with array default values `('{one,two}'::text[])::varchar[]`, remove `ArrayParser::parseString()` (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
