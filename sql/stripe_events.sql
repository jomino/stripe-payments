CREATE TABLE `stripe_events` (
  `id` int(9) unsigned NOT NULL auto_increment,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `status` varchar(32) NOT NULL default '',
  `uuid` varchar(256) NOT NULL default '',
  `name` varchar(128) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `amount` smallint unsigned NOT NULL default 0,
  `product` varchar(128) NOT NULL default '',
  `method` varchar(128) NOT NULL default '',
  `token` varchar(256) NOT NULL default '',
  `skey` varchar(256) NOT NULL default '',
  `ckey` varchar(256) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;