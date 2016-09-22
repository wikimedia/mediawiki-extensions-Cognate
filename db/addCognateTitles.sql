-- Add cognate_titles table

CREATE TABLE IF NOT EXISTS /*_*/cognate_titles (
  cgti_site VARBINARY(32) NOT NULL,
  cgti_namespace INT NOT NULL,
  cgti_title VARBINARY(255),
  cgti_key VARBINARY(255) NOT NULL,
  PRIMARY KEY (cgti_site, cgti_namespace, cgti_title)
  )/*$wgDBTableOptions*/;

CREATE INDEX /*i*/cgti_keys ON /*_*/cognate_titles (cgti_site, cgti_namespace, cgti_key);