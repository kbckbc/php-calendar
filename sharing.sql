mysql> show create table sharing;
+---------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Table   | Create Table                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
+---------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| sharing | CREATE TABLE `sharing` (
  `sharing_id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `giver_id` smallint unsigned NOT NULL,
  `taker_id` smallint unsigned NOT NULL,
  PRIMARY KEY (`sharing_id`),
  KEY `giver_id` (`giver_id`),
  KEY `taker_id` (`taker_id`),
  CONSTRAINT `sharing_ibfk_1` FOREIGN KEY (`giver_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `sharing_ibfk_2` FOREIGN KEY (`taker_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 |
+---------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
1 row in set (0.00 sec)
