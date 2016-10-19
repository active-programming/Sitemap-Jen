
ALTER TABLE `#__sitemapjen_links` ADD `md5_loc` VARCHAR(32) NULL DEFAULT NULL AFTER `loc`;
ALTER TABLE `#__sitemapjen_links` ADD UNIQUE(`md5_loc`);
ALTER TABLE `#__sitemapjen_links` ADD INDEX(`md5_loc`);