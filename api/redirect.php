<?php

header("Access-Control-Allow-Origin: *");

include "../helpers/common_helper.php";
include "../helpers/function_helper.php";

use Database\DB;
use Cafe24\Cafe24;

$cafe24 = new Cafe24();

// 최초 INSTALL
// $sql = "
//     CREATE TABLE `wg_goodsOption` (
//   `sno` int(11) NOT NULL AUTO_INCREMENT,
//   `productNo` int(11) NOT NULL,
//   `variantCode` varchar(50) NOT NULL,
//   `valueText1` varchar(150) DEFAULT NULL COMMENT '첫번째 옵션 텍스트',
//   `value1` varchar(150) DEFAULT NULL COMMENT '첫번째 옵션 값',
//   `valueText2` varchar(150) DEFAULT NULL COMMENT '두번째 옵션 텍스트',
//   `value2` varchar(150) DEFAULT NULL COMMENT '두번째 옵션 값',
//   `valueText3` varchar(150) DEFAULT NULL COMMENT '세번째 옵션 텍스트',
//   `value3` varchar(150) DEFAULT NULL COMMENT '세번째 옵션 값',
//   `valueText4` varchar(150) DEFAULT NULL COMMENT '네번째 옵션 텍스트',
//   `value4` varchar(150) DEFAULT NULL COMMENT '네번째 옵션 값',
//   `valueText5` varchar(150) DEFAULT NULL COMMENT '다섯번째 옵션 텍스트',
//   `value5` varchar(150) DEFAULT NULL COMMENT '다섯번째 옵션 값',
//   `customVariantCode` varchar(50) DEFAULT NULL,
//   `display` char(1) DEFAULT 'T',
//   `selling` char(1) DEFAULT 'T',
//   `additionalAmount` decimal(10,2) DEFAULT '0.00',
//   `useInventory` char(1) DEFAULT 'F',
//   `importantInventory` char(1) DEFAULT 'A',
//   `inventoryControlType` char(1) DEFAULT 'A',
//   `displaySoldout` char(1) DEFAULT 'F',
//   `quantity` int(11) DEFAULT '0',
//   `safetyInventory` int(11) DEFAULT '0',
//   `image` varchar(255) DEFAULT NULL,
//   `regDt` datetime DEFAULT CURRENT_TIMESTAMP,
//   PRIMARY KEY (`sno`),
//   UNIQUE KEY `uk_variant_code` (`variantCode`),
//   KEY `idx_product_no` (`productNo`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
// ";
// $cafe24->db->query($sql);

// $sql = "
// CREATE TABLE `wg_cafe24Token` (
//  `sno` int(10) NOT NULL,
//  `mallId` varchar(200) NOT NULL,
//  `clientId` varchar(200) NOT NULL,
//  `accessToken` varchar(200) NOT NULL,
//  `refreshToken` varchar(200) NOT NULL,
//  `accessTokenExpiresDt` datetime NOT NULL,
//  `refreshTokenExpiresDt` datetime NOT NULL,
//  `regDt` datetime NOT NULL,
//  `modDt` datetime NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
// ";
// $result = $cafe24->db->query($sql);

// $sql = "
// 	ALTER TABLE `wg_cafe24Token`
// 	ADD PRIMARY KEY (`sno`),
// 	ADD UNIQUE KEY `mallId` (`mallId`,`clientId`);
// ";
// $cafe24->db->query($sql);

// $sql = "
// 	ALTER TABLE `wg_cafe24Token`
// 	MODIFY `sno` int(10) NOT NULL AUTO_INCREMENT;
// 	COMMIT;
// ";
// $cafe24->db->query($sql);

// $sql = "
// CREATE TABLE `wg_goods` ( `shop_no` INT NOT NULL , `product_no` INT NOT NULL , `product_code` VARCHAR(100) NOT NULL , `custom_product_code` VARCHAR(100) NOT NULL , `product_name` VARCHAR(200) NOT NULL , `eng_product_name` VARCHAR(200) NOT NULL , `supply_product_name` VARCHAR(200) NOT NULL , `internal_product_name` VARCHAR(200) NOT NULL , `model_name` VARCHAR(100) NOT NULL , `price_excluding_tax` INT NOT NULL , `price` INT NOT NULL , `retail_price` INT NOT NULL , `supply_price` INT NOT NULL , `display` VARCHAR(1) NOT NULL , `selling` VARCHAR(1) NOT NULL , `product_condition` VARCHAR(1) NOT NULL , `product_used_month` VARCHAR(10) NOT NULL , `summary_description` VARCHAR(100) NOT NULL , `product_tag` TEXT NOT NULL , `margin_rate` INT NOT NULL , `tax_calculation` VARCHAR(1) NOT NULL , `tax_type` VARCHAR(1) NOT NULL , `tax_rate` INT NOT NULL , `price_content` INT NOT NULL , `buy_limit_by_product` VARCHAR(1) NOT NULL , `buy_limit_type` INT NOT NULL , `buy_group_list` INT NOT NULL , `buy_member_id_list` INT NOT NULL , `repurchase_restriction` VARCHAR(1) NOT NULL , `single_purchase_restriction` VARCHAR(1) NOT NULL , `buy_unit_type` INT NOT NULL , `buy_unit` INT NOT NULL , `order_quantity_limit_type` INT NOT NULL , `minimum_quantity` INT NOT NULL , `maximum_quantity` INT NOT NULL , `points_by_product` VARCHAR(1) NOT NULL , `points_setting_by_payment` INT NOT NULL , `points_amount` INT NOT NULL , `except_member_points` VARCHAR(1) NOT NULL , `product_volume` INT NOT NULL , `adult_certification` VARCHAR(1) NOT NULL , `detail_image` TEXT NOT NULL , `list_image` TEXT NOT NULL , `tiny_image` TEXT NOT NULL , `small_image` TEXT NOT NULL , `use_naverpay` VARCHAR(1) NOT NULL , `naverpay_type` VARCHAR(1) NOT NULL , `manufacturer_code` VARCHAR(20) NOT NULL , `trend_code` VARCHAR(20) NOT NULL , `brand_code` VARCHAR(20) NOT NULL , `supplier_code` VARCHAR(20) NOT NULL , `made_date` INT NOT NULL , `release_date` INT NOT NULL , `expiration_date` INT NOT NULL , `origin_classification` VARCHAR(1) NOT NULL , `origin_place_no` INT NOT NULL , `origin_place_value` INT NOT NULL , `made_in_code` VARCHAR(20) NOT NULL , `icon_show_period` INT NOT NULL , `icon` INT NOT NULL , `hscode` VARCHAR(20) NOT NULL , `product_weight` INT NOT NULL , `product_material` INT NOT NULL , `created_date` DATETIME NOT NULL , `updated_date` DATETIME NOT NULL , `english_product_material` INT NOT NULL , `cloth_fabric` INT NOT NULL , `list_icon` INT NOT NULL , `approve_status` INT NOT NULL , `classification_code` VARCHAR(20) NOT NULL , `sold_out` VARCHAR(1) NOT NULL , `additional_price` INT NOT NULL , `clearance_category_eng` TEXT NOT NULL , `clearance_category_kor` TEXT NOT NULL , `clearance_category_code` TEXT NOT NULL , `exposure_limit_type` VARCHAR(1) NOT NULL , `exposure_group_list` INT NOT NULL , `set_product_type` INT NOT NULL , `use_kakaopay` INT NOT NULL , `shipping_fee_by_product` VARCHAR(1) NOT NULL , `shipping_fee_type` VARCHAR(1) NOT NULL , `main` INT NOT NULL , `market_sync` VARCHAR(1) NOT NULL )
// ";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD options TEXT NOT NULL AFTER `market_sync`;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD category INT NOT NULL AFTER options;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD `additionalImages` TEXT NOT NULL AFTER category;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD `displayFl` VARCHAR(1) NOT NULL AFTER `additionalImages`;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD `displayFl2` VARCHAR(1) NOT NULL AFTER `additionalImages`;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goods` ADD UNIQUE( `shop_no`, `product_no`);";
// $cafe24->db->query($sql);

// $sql = "
// ALTER TABLE `wg_goods` ADD `pcDiscountPrice` INT NOT NULL AFTER `displayFl`;
// ALTER TABLE `wg_goods` ADD `moDiscountPrice` INT NOT NULL AFTER `pcDiscountPrice`;
// ALTER TABLE `wg_goods` ADD `appDiscountPrice` INT NOT NULL AFTER `moDiscountPrice`;
// ";
// $cafe24->db->query($sql);

// $sql = "CREATE TABLE `wg_category` ( `category_no` INT NOT NULL , `category_depth` INT NOT NULL , `category_name` VARCHAR(200) NOT NULL , `parent_category_no` INT NOT NULL , `use_display` VARCHAR(1) NOT NULL ,`display_order` VARCHAR(20) NOT NULL , `regDt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ) ENGINE = InnoDB;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_category` ADD `goodsList` TEXT NOT NULL AFTER `regDt`;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_category` ADD `display_order` INT NOT NULL AFTER `productList`;";
// $cafe24->db->query($sql);

// $sql ="
//    CREATE TABLE wg_goodsLinkCategory (
//     `sno` int(11) NOT NULL AUTO_INCREMENT,
//     `categoryNo` INT NOT NULL,
//     `goodsNo` INT NOT NULL,
//     `regDt` datetime DEFAULT CURRENT_TIMESTAMP,
//     PRIMARY KEY (`sno`)
// ) ENGINE=InnoDB;
// ";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_goodsLinkCategory` ADD UNIQUE(`categoryNo`, `goodsNo`);";
// $cafe24->db->query($sql);

// $sql = "CREATE TABLE `wg_board` (
//  `sno` int(11) NOT NULL,
//  `board_no` int(11) NOT NULL,
//  `article_no` int(11) NOT NULL,
//  `shop_no` int(11) NOT NULL,
//  `product_no` varchar(1000) NOT NULL,
//  `url` varchar(1000) NOT NULL,
//  `writer` varchar(100) NOT NULL,
//  `member_id` varchar(200) DEFAULT NULL,
//  `title` varchar(100) NOT NULL,
//  `content` varchar(10000) NOT NULL,
//  `hit` int(11) DEFAULT NULL,
//  `attach_file_urls` text,
//  `created_date` datetime NOT NULL,
//  `modify_date` datetime NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_board`
//  ADD PRIMARY KEY (`sno`),
//  ADD KEY `board_no` (`board_no`,`article_no`,`writer`,`title`);";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_board`
//  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT;";
// $cafe24->db->query($sql);


 //$sql = "CREATE TABLE `wg_order` (
 //  `sno` int(11) NOT NULL,
 //  `order_id` varchar(100) NOT NULL,
 //  `member_id` varchar(100) NOT NULL,
 //  `shipping_status` varchar(1000) NOT NULL,
 //  `payment_amount` int(11) NOT NULL,
 //  `shipping_fee_detail` varchar(1000) NOT NULL,
 //  `items` text NOT NULL,
 //  `order_date` datetime NOT NULL,
 //  `payment_date` datetime NOT NULL,
 //  `regDt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
 //  `payment_method` varchar(50) NOT NULL,
 // `points_spent_amount` decimal(10,2) NOT NULL
 //) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
 //$cafe24->db->query($sql);

 //$sql = "ALTER TABLE `wg_order`
 // ADD PRIMARY KEY (`sno`)";
 //$cafe24->db->query($sql);

 //$sql = "ALTER TABLE `wg_order`
 //	ADD PRIMARY KEY (`sno`),
 //	ADD UNIQUE KEY `order_id` (`order_id`);";
 //$cafe24->db->query($sql);

 //$sql = "ALTER TABLE `wg_order`
 // MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT";
 //$cafe24->db->query($sql);

 //$sql = "CREATE TABLE `wg_orderGoods` (
 // `sno` int(11) NOT NULL,
 // `order_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
 // `member_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shop_no` int(11) DEFAULT NULL,
 // `item_no` int(11) DEFAULT NULL,
 // `order_item_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `variant_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_no` int(11) DEFAULT NULL,
 // `product_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `internal_product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `custom_product_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `custom_variant_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `eng_product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `option_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `option_value` text COLLATE utf8mb4_unicode_ci,
 // `option_value_default` text COLLATE utf8mb4_unicode_ci,
 // `additional_option_value` text COLLATE utf8mb4_unicode_ci,
 // `additional_option_values` text COLLATE utf8mb4_unicode_ci,
 // `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_name_default` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_price` decimal(10,2) DEFAULT NULL,
 // `option_price` decimal(10,2) DEFAULT NULL,
 // `additional_discount_price` decimal(10,2) DEFAULT NULL,
 // `coupon_discount_price` decimal(10,2) DEFAULT NULL,
 // `app_item_discount_amount` decimal(10,2) DEFAULT NULL,
 // `payment_amount` decimal(10,2) DEFAULT NULL,
 // `quantity` int(11) DEFAULT NULL,
 // `product_tax_type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `tax_rate` decimal(5,2) DEFAULT NULL,
 // `supplier_product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `supplier_transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `supplier_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `tracking_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shipping_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `claim_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `claim_reason_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `claim_reason` text COLLATE utf8mb4_unicode_ci,
 // `refund_bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `refund_bank_account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `refund_bank_account_holder` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `post_express_flag` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `order_status` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `request_undone` text COLLATE utf8mb4_unicode_ci,
 // `order_status_additional_info` text COLLATE utf8mb4_unicode_ci,
 // `claim_quantity` int(11) DEFAULT NULL,
 // `status_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `status_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `open_market_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `bundled_shipping_type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shipping_company_id` int(11) DEFAULT NULL,
 // `shipping_company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shipping_company_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_bundle` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_bundle_no` int(11) DEFAULT NULL,
 // `product_bundle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_bundle_name_default` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_bundle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `was_product_bundle` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `original_bundle_item_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `naver_pay_order_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `naver_pay_claim_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `individual_shipping_fee` decimal(10,2) DEFAULT NULL,
 // `shipping_fee_type` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shipping_fee_type_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `shipping_payment_option` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `payment_info_id` int(11) DEFAULT NULL,
 // `original_item_no` text COLLATE utf8mb4_unicode_ci,
 // `store_pickup` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `ordered_date` datetime DEFAULT NULL,
 // `shipped_date` datetime DEFAULT NULL,
 // `delivered_date` datetime DEFAULT NULL,
 // `purchaseconfirmation_date` datetime DEFAULT NULL,
 // `cancel_date` datetime DEFAULT NULL,
 // `return_confirmed_date` datetime DEFAULT NULL,
 // `return_request_date` datetime DEFAULT NULL,
 // `return_collected_date` datetime DEFAULT NULL,
 // `cancel_request_date` datetime DEFAULT NULL,
 // `refund_date` datetime DEFAULT NULL,
 // `exchange_request_date` datetime DEFAULT NULL,
 // `exchange_date` datetime DEFAULT NULL,
 // `product_material` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_material_eng` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `cloth_fabric` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_weight` decimal(10,2) DEFAULT NULL,
 // `volume_size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `volume_size_weight` decimal(10,2) DEFAULT NULL,
 // `clearance_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `clearance_category_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `clearance_category_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `hs_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `one_plus_n_event` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `origin_place` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `origin_place_no` int(11) DEFAULT NULL,
 // `made_in_code` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `origin_place_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `gift` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `item_granting_gift` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `subscription` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `product_bundle_list` text COLLATE utf8mb4_unicode_ci,
 // `market_cancel_request` text COLLATE utf8mb4_unicode_ci,
 // `market_cancel_request_quantity` int(11) DEFAULT NULL,
 // `market_fail_reason` text COLLATE utf8mb4_unicode_ci,
 // `market_fail_reason_guide` text COLLATE utf8mb4_unicode_ci,
 // `market_item_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `market_custom_variant_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `option_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `options` text COLLATE utf8mb4_unicode_ci,
 // `market_discount_amount` decimal(10,2) DEFAULT NULL,
 // `labels` text COLLATE utf8mb4_unicode_ci,
 // `order_status_before_cs` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `supply_price` decimal(10,2) DEFAULT NULL,
 // `multi_invoice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 // `send_no` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '발신주문번호',
 // `board_reward_board_no` int(11) NOT NULL,
 // `board_reward_no` int(11) NOT NULL,
 // `board_reward_member_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '게시판 리워드 지급 id',
 // `board_reward_fl` enum('y','n') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'n' COMMENT '게시판 리워드 지급 여부',
 // `board_reward_amount` decimal(10,2) DEFAULT NULL COMMENT '게시글 리워드 지급 금액',
 // `regDt` datetime NOT NULL,
 // `modDt` datetime NOT NULL
 //) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
 //$cafe24->db->query($sql);


 //$sql ="ALTER TABLE `wg_orderGoods`
 // ADD PRIMARY KEY (`sno`),
 // ADD UNIQUE KEY `order_item_code` (`order_item_code`),
 // ADD KEY `order_id` (`order_id`),
 // ADD KEY `member_id` (`member_id`),
 // ADD KEY `send_no` (`send_no`);";
 //$cafe24->db->query($sql);


 //$sql ="ALTER TABLE `wg_orderGoods`
 // MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT;";
 //$cafe24->db->query($sql);

// $sql ="CREATE TABLE `wg_member` (
//  `sno` int(11) NOT NULL,
//  `memberType` varchar(1) NOT NULL,
//  `companyType` varchar(10) DEFAULT NULL COMMENT '사업자 구분',
//  `memId` varchar(200) NOT NULL,
//  `memNm` varchar(50) NOT NULL,
//  `cellphone` varchar(30) NOT NULL,
//  `regDt` datetime NOT NULL,
//  `modDt` datetime NOT NULL,
//  `recommId` varchar(200) NOT NULL,
//  `recommCnt` int(11) NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// $cafe24->db->query($sql);

// $sql ="ALTER TABLE `wg_member`
//  ADD PRIMARY KEY (`sno`)";
// $cafe24->db->query($sql);


// $sql ="ALTER TABLE `wg_member`
//  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT;";
// $cafe24->db->query($sql);

// $sql = "CREATE TABLE `wg_recommSetting` (
//  `sno` int(11) NOT NULL,
//  `useFl` enum('y','n') NOT NULL DEFAULT 'n' COMMENT '사용여부',
//  `unitPrecision` int(11) DEFAULT NULL COMMENT '리워드 절사 단위',
//  `unitRound` varchar(10) DEFAULT NULL COMMENT '리워드 절사',
//  `boardRewardUseFl` enum('y','n') NOT NULL DEFAULT 'n',
//  `boardRewardRate` decimal(5,1) DEFAULT NULL COMMENT '게시글 리워드 비율',
//  `boardRewardGroupNo` varchar(200) DEFAULT NULL COMMENT '리워드 적용 회원등급',
//  `boardRewardStatus` varchar(10) DEFAULT NULL COMMENT '적립 가능한 주문상태',
//  `boardRewardPeriod` int(11) DEFAULT NULL COMMENT '적립시점',
//  `boardRewardlimitPeriodMonth` int(11) NOT NULL,
//  `boardRewardCommissionLimit` int(11) NOT NULL,
//  `regDt` datetime NOT NULL,
//  `modDt` datetime NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// $cafe24->db->query($sql);

// $sql = "INSERT INTO `wg_recommSetting` (`sno`, `useFl`, `unitPrecision`, `unitRound`, `boardRewardUseFl`, `boardRewardRate`, `boardRewardGroupNo`, `boardRewardStatus`, `boardRewardPeriod`, `boardRewardlimitPeriodMonth`, `boardRewardCommissionLimit`, `regDt`, `modDt`) VALUES
// (1, 'y', 1, 'C', 'y', 1.0, '13||14||12||11||5', 'P', 0, 3, 1200, '2025-01-07 17:30:13', '2025-02-19 18:03:50');";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_recommSetting`
//  ADD PRIMARY KEY (`sno`);";
// $cafe24->db->query($sql);

// $sql = "CREATE TABLE `wg_recommCommission` (
//  `sno` int(11) NOT NULL,
//  `target` varchar(50) DEFAULT NULL COMMENT '혜택대상',
//  `sort` int(11) DEFAULT NULL,
//  `priceRangeStart` int(11) DEFAULT NULL COMMENT '금액 구간 시작',
//  `priceRangeEnd` int(11) DEFAULT NULL COMMENT '금액 구간 끝',
//  `commissionRate` decimal(5,1) DEFAULT NULL COMMENT '커미션 비율',
//  `commissionLimit` int(11) DEFAULT NULL COMMENT '커미션 지급 한도',
//  `limitPeriodMonth` int(11) DEFAULT NULL COMMENT '지급한도 기준 개월',
//  `regDt` datetime NOT NULL,
//  `modDt` datetime NOT NULL
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
// $cafe24->db->query($sql);

// $sql = "INSERT INTO `wg_recommCommission` (`sno`, `target`, `sort`, `priceRangeStart`, `priceRangeEnd`, `commissionRate`, `commissionLimit`, `limitPeriodMonth`, `regDt`, `modDt`) VALUES
// (19, 'normal', 0, 0, 30, 1.0, 5000, 6, '2025-01-07 17:53:17', '2025-02-19 18:03:50'),
// (21, 'business', 0, 0, 200, 2.0, 7000, 3, '2025-01-07 17:53:17', '2025-02-19 18:03:50'),
// (22, 'normal', 1, 30, 100, 2.0, 200000, 3, '2025-01-07 17:53:17', '2025-02-19 18:03:50'),
// (24, 'business', 1, 200, 1000, 2.0, 100000, 3, '2025-01-07 17:53:17', '2025-02-19 18:03:50'),
// (42, 'business', 2, 1000, 3000, 3.0, 100000, 3, '2025-01-08 10:57:01', '2025-02-19 18:03:50'),
// (150, 'normal', 2, 100, 300, 3.0, 300000, 3, '2025-01-08 11:21:44', '2025-02-19 18:03:50');";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_recommCommission`
//  ADD PRIMARY KEY (`sno`),
//  ADD UNIQUE KEY `unique_price_target` (`sort`,`target`);";
// $cafe24->db->query($sql);

// $sql = "ALTER TABLE `wg_recommCommission`
//  MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;";
// $cafe24->db->query($sql);


// $sql = "
// 	CREATE TABLE `wg_apiLog` (
//   `sno` int(11) NOT NULL,
//   `apiType` varchar(50) NOT NULL,
//   `requestData` text NOT NULL,
//   `responseData` text NOT NULL,
//   `regDt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
// ";
// $cafe24->db->query($sql);
// $sql = "
// 	ALTER TABLE `wg_apiLog`
//   ADD PRIMARY KEY (`sno`);
// ";
// $cafe24->db->query($sql);
// $sql = "
// 	ALTER TABLE `wg_apiLog`
//   MODIFY `sno` int(11) NOT NULL AUTO_INCREMENT;
// ";
// $cafe24->db->query($sql);
// $sql = "
// 	CREATE TABLE wg_benefit (`sno` INT NOT NULL AUTO_INCREMENT , `benefit_no` INT NOT NULL , `use_benefit` VARCHAR(1) NOT NULL , `benefit_name` VARCHAR(200) NOT NULL , `benefit_division` VARCHAR(1) NOT NULL , `benefit_type` VARCHAR(2) NOT NULL , `use_benefit_period` VARCHAR(1) NOT NULL , `benefit_start_date` DATETIME NOT NULL , `benefit_end_date` DATETIME NOT NULL , `platform_types` VARCHAR(20) NOT NULL , `use_group_binding` VARCHAR(1) NOT NULL , `customer_group_list` VARCHAR(100) NOT NULL , `product_binding_type` VARCHAR(1) NOT NULL , `use_except_category` VARCHAR(1) NOT NULL , `available_coupon` VARCHAR(1) NOT NULL , `created_date` DATETIME NOT NULL , `reg_dt` DATETIME NOT NULL , PRIMARY KEY (`sno`));
// ";
// $cafe24->db->query($sql);

// $sql = "
// 	CREATE TABLE `wg_orderReceiver` (
//   `id` int(11) NOT NULL AUTO_INCREMENT,
//   `shop_no` int(11) DEFAULT NULL,
//   `name` varchar(100) DEFAULT NULL,
//   `name_furigana` varchar(100) DEFAULT NULL,
//   `phone` varchar(20) DEFAULT NULL,
//   `cellphone` varchar(20) DEFAULT NULL,
//   `virtual_phone_no` varchar(20) DEFAULT NULL,
//   `zipcode` varchar(10) DEFAULT NULL,
//   `address1` varchar(255) DEFAULT NULL,
//   `address2` varchar(255) DEFAULT NULL,
//   `address_state` varchar(100) DEFAULT NULL,
//   `address_city` varchar(100) DEFAULT NULL,
//   `address_street` varchar(255) DEFAULT NULL,
//   `address_full` text DEFAULT NULL,
//   `name_en` varchar(100) DEFAULT NULL,
//   `city_en` varchar(100) DEFAULT NULL,
//   `state_en` varchar(100) DEFAULT NULL,
//   `street_en` varchar(255) DEFAULT NULL,
//   `country_code` varchar(10) DEFAULT NULL,
//   `country_name` varchar(100) DEFAULT NULL,
//   `country_name_en` varchar(100) DEFAULT NULL,
//   `shipping_message` text DEFAULT NULL,
//   `clearance_information_type` varchar(50) DEFAULT NULL,
//   `clearance_information` text DEFAULT NULL,
//   `wished_delivery_date` date DEFAULT NULL,
//   `wished_delivery_time` varchar(50) DEFAULT NULL,
//   `shipping_code` varchar(50) DEFAULT NULL,
//   `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
//   `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
//   PRIMARY KEY (`id`),
//   KEY `idx_shipping_code` (`shipping_code`),
//   KEY `idx_name` (`name`),
//   KEY `idx_phone` (`phone`),
//   KEY `idx_cellphone` (`cellphone`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
// ";
// $cafe24->db->query($sql);

// exit;

$cafe24->connectNewMall();
//$cafe24->setScript();

// 인증코드 요청 URL
// https://{mall_id}.cafe24api.com/api/v2/oauth/authorize?response_type=code&client_id={client_id}&state={encode_csrf_token}&redirect_uri={encode_redirect_uri}&scope={scope}
// https://hoyatool.cafe24api.com/api/v2/oauth/authorize?response_type=code&client_id=1H8eTbC8QasA3PtJwD1x4A&state=200&redirect_uri=https://hoyatools.mycafe24.com/api/redirect.php&scope=mall.read_product