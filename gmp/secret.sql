
CREATE TABLE secrets (
  id int(12) unsigned NOT NULL auto_increment,
  secret_key varchar(255) default NULL,
  username varchar(255) default NULL,
  PRIMARY KEY  (id),
  KEY id (id)
) TYPE=MyISAM;
