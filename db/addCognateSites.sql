-- Add cognate_sites table

CREATE TABLE IF NOT EXISTS /*_*/cognate_sites (
  cgsi_key BIGINT SIGNED PRIMARY KEY NOT NULL,
  cgsi_dbname VARBINARY(32) NOT NULL,
  cgsi_interwiki VARBINARY(32) NOT NULL
  )/*$wgDBTableOptions*/;
