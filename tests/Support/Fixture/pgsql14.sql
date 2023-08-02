DROP TABLE IF EXISTS "table_with_multirange" CASCADE;

CREATE TABLE "table_with_multirange" (
    id serial not null primary key,
    int4multirange_col int4multirange,
    int8multirange_col int8multirange,
    nummultirange_col nummultirange,
    datemultirange_col datemultirange
);
