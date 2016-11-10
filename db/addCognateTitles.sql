-- Add cognate_titles table

CREATE TABLE IF NOT EXISTS /*_*/cognate_titles (
  cgti_raw VARBINARY(255) NOT NULL,
  cgti_raw_key BIGINT SIGNED NOT NULL PRIMARY KEY,
  cgti_normalized_key BIGINT SIGNED NOT NULL
  )/*$wgDBTableOptions*/;

CREATE INDEX /*i*/cgti_normalized_keys ON /*_*/cognate_titles (cgti_normalized_key);
