-- Add article assessments table

CREATE TABLE IF NOT EXISTS /*_*/inter_language_titles (
   ilt_language        VARCHAR(20) NOT NULL,
   ilt_title           VARCHAR(255) NOT NULL
  )/*$wgDBTableOptions*/;

CREATE INDEX /*i*/ilt_language ON /*_*/ inter_language_titles (ilt_language);
CREATE UNIQUE INDEX /*i*/ilt_title_language ON /*_*/ inter_language_titles (ilt_title, ilt_language);