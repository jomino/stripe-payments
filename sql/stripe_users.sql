CREATE TABLE `stripe_users` (
  `id` int(9) unsigned NOT NULL auto_increment,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `active` tinyint unsigned NOT NULL default 0,
  `uuid` varchar(256) NOT NULL default '',
  `name` varchar(128) NOT NULL default '',
  `email` varchar(128) NOT NULL default '',
  `pkey` varchar(256) NOT NULL default '',
  `skey` varchar(256) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;