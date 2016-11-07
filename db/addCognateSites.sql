-- Add cognate_sites table

CREATE TABLE IF NOT EXISTS /*_*/cognate_sites (
  cgsi_dbname VARBINARY(32) PRIMARY KEY NOT NULL,
  cgsi_interwiki VARBINARY(32) NOT NULL
  )/*$wgDBTableOptions*/;
