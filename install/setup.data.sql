# --------------------------------------------------------

#
# Table structure for table `{PREFIX}emerchant_orders`
#


CREATE TABLE `{PREFIX}emerchant_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cart` text NOT NULL,
  `form` text NOT NULL,
  `date` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `userId` int(11) NOT NULL,
  `comment` text NOT NULL,
  `dl` tinyint(4) NOT NULL DEFAULT '1',
  `name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8;



CREATE TABLE `{PREFIX}emerchant_orders_thin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `cart` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8;

INSERT INTO `{PREFIX}system_eventnames` (`id`, `name`, `service`, `groupname`) VALUES
(1048, 'OnEmerchantInit', 77, 'eMerchant'),
(1049, 'OnEmerchantMakePosition', 77, 'eMerchant'),
(1050, 'OnEmerchantAddToCart', 77, 'eMerchant'),
(1051, 'OnEmerchantRemoveFromCart', 77, 'eMerchant'),
(1052, 'OnEmerchantRecountPositionCart', 77, 'eMerchant'),
(1053, 'OnEmerchantSaveCart', 77, 'eMerchant'),
(1055, 'OnEmerchantDifferentPrices', 77, 'eMerchant'),
(1056, 'OnEmerchantSaveOrder', 77, 'eMerchant'),
(1057, 'OnEmerchantSendLetter', 77, 'eMerchant'),
(1058, 'OnEmerchantClearCart', 77, 'eMerchant'),
(1059, 'OnEmerchantChangeStatus', 77, 'eMerchant');