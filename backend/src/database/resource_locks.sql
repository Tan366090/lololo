CREATE TABLE IF NOT EXISTS `resource_locks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` varchar(50) NOT NULL,
  `locked_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_id` (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 