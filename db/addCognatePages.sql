-- Add cognate_pages table

CREATE TABLE IF NOT EXISTS /*_*/cognate_pages (
  cgpa_site BIGINT SIGNED NOT NULL,
  cgpa_namespace INT NOT NULL,
  cgpa_title BIGINT SIGNED NOT NULL,
  PRIMARY KEY (cgpa_site, cgpa_namespace, cgpa_title)
  )/*$wgDBTableOptions*/;
