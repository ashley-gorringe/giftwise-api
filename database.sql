SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_uid` varchar(36) NOT NULL,
  `is_primary` int(1) NOT NULL DEFAULT '0',
  `is_private` int(1) NOT NULL DEFAULT '0',
  `account_name` tinytext NOT NULL,
  `image_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `account_share` (
  `account_share_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `share_code` varchar(6) NOT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `image` (
  `image_id` int(11) NOT NULL,
  `image_uid` varchar(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `item` (
  `item_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `item_uid` varchar(36) NOT NULL,
  `title` tinytext NOT NULL,
  `description` text NOT NULL,
  `url` tinytext,
  `currency` varchar(3) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `visibility` int(1) NOT NULL DEFAULT '0',
  `image_id` int(11) DEFAULT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `login_token` (
  `login_token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(36) NOT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activity_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `email` tinytext NOT NULL,
  `password` tinytext NOT NULL,
  `name_preferred` tinytext NOT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activity_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_uid` (`account_uid`),
  ADD KEY `account_user_id` (`user_id`),
  ADD KEY `account_image_id` (`image_id`);

ALTER TABLE `account_share`
  ADD PRIMARY KEY (`account_share_id`),
  ADD UNIQUE KEY `account_share_account_id` (`account_id`);

ALTER TABLE `image`
  ADD PRIMARY KEY (`image_id`);

ALTER TABLE `item`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_uid` (`item_uid`),
  ADD KEY `item_account_id` (`account_id`),
  ADD KEY `item_image_id` (`image_id`);

ALTER TABLE `login_token`
  ADD PRIMARY KEY (`login_token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `login_token_user_id` (`user_id`);

ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);


ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `account_share`
  MODIFY `account_share_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `image`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `item`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `login_token`
  MODIFY `login_token_id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `account_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `image` (`image_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `account_share`
  ADD CONSTRAINT `account_share_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `item_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `image` (`image_id`) ON DELETE SET NULL ON UPDATE NO ACTION;

ALTER TABLE `login_token`
  ADD CONSTRAINT `login_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
