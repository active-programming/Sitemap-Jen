
ALTER TABLE `smj_sitemapjen_links` ADD `md5_loc` VARCHAR(32) NULL DEFAULT NULL AFTER `loc`;
ALTER TABLE `smj_sitemapjen_links` ADD UNIQUE(`md5_loc`);
ALTER TABLE `smj_sitemapjen_links` ADD INDEX(`md5_loc`);