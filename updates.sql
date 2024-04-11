CREATE TABLE `voucher_rules` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`shop` INT NULL DEFAULT '0',
	`category` INT NULL DEFAULT '0',
	`maxwallet` DOUBLE NULL DEFAULT '0.85',
	PRIMARY KEY (`id`),
	INDEX `shop` (`shop`),
	INDEX `category` (`category`)
)
COLLATE='utf8mb4_unicode_ci'
;


ALTER TABLE `vouchers`
	ADD COLUMN `wallet` INT(11) NOT NULL;


ALTER TABLE `vouchers`
	ADD COLUMN `sfvoucher` VARCHAR(50) NULL DEFAULT NULL COLLATE 'latin1_swedish_ci';



BEGIN

	DECLARE i INT;
	DECLARE voucherCode VARCHAR(30);
	DECLARE sessionId INT;
	DECLARE a_gen_time DATETIME;

	SET i = 0;
	SET voucherCode = '';
	SET sessionId = 0;
	SET a_gen_time = NOW();
	
	SELECT `session_id` INTO sessionId FROM `vouchers` WHERE `session_id`>=0 ORDER BY `voucher_id` DESC LIMIT 1;	
	SET sessionId = sessionId + 1;

	WHILE i < a_num_vouchers DO

		SET voucherCode = CONCAT(a_prefix, SUBSTRING(CRC32(RAND()), 1, 13));
		
		#IF EXISTS (SELECT count(`session_id`) FROM `vouchers` WHERE `code` = voucherCode)
		#	SET voucherCode = CONCAT(a_prefix, SUBSTRING(CRC32(RAND()), 1, 13));		
		#END IF;	

		INSERT INTO vouchers(
			`shop_id`,
			`account_id`,
			`code`,
			`points`,
			`content_type_id`,
			`content_id`,
			`voucher_type`,
			`user_id`,
			`gen_time`,
			`session_id`,
			`generator_id`,
			`exp_time`,
			`start_time`,
			`points_exp_time`,
			`points_start_time`,
			`wallet`
		) VALUES (
			a_shop_id,
			a_account_id,
			voucherCode,
			a_points,
			a_content_type_id,
			a_content_id,
			a_voucher_type,
			a_user_id,
			a_gen_time,
			sessionId,
			a_user_id,
			a_exp_time,
			a_start_time,
			a_points_exp_time,
			a_points_start_time,
			a_points
		);
		
		SET i = i + 1;

	END WHILE;
	
	SELECT * FROM `vouchers` WHERE `session_id` = sessionId AND `gen_time`=a_gen_time;

END
	



BEGIN

	DECLARE ret_errcode INT;
	DECLARE ret_code VARCHAR(255);
	DECLARE ret_points INT;
	DECLARE ret_contentType INT;
	DECLARE ret_voucherType INT;
	DECLARE ret_shopId INT;
	DECLARE ret_startTime DATETIME;
	DECLARE ret_expTime DATETIME;
	DECLARE ret_pointsStartTime DATETIME;
	DECLARE ret_pointsExpTime DATETIME;
	DECLARE ret_status INT;
	DECLARE ret_pass VARCHAR(255);

	SET ret_errcode = 0;
	SET ret_code = '';
	SET ret_points = 0;
	SET ret_contentType = 0;
	SET ret_voucherType = 0;
	SET ret_shopId = 0;
	SET ret_startTime = NOW();
	SET ret_expTime = NOW();
	SET ret_pointsStartTime = NOW();
	SET ret_pointsExpTime = NOW();
	SET ret_status = 0;
	SET ret_pass = '';
	
	SELECT 
		`code`, `points`, `content_type_id`, `voucher_type`, `shop_id`, `start_time`, `exp_time`, `points_start_time`, `points_exp_time`, `status`, `pass_id` 
	INTO ret_code, ret_points, ret_contentType, ret_voucherType, ret_shopId, ret_startTime, ret_expTime, ret_pointsStartTime, ret_pointsExpTime, ret_status, ret_pass 
	FROM vouchers WHERE `code`=a_code;
		
	#check voucher exists
	IF ret_code = '' THEN
		SET ret_errcode = 1;
	END IF;
	
	#check shop id is correct
	IF ret_code <> '' AND ret_shopId != a_shopId THEN
		SET ret_errcode = 2;
	END IF;

	#check voucher has started
	IF ret_code <> '' AND ret_startTime > NOW() THEN
		SET ret_errcode = 3;
	END IF;

	#check voucher is not expired
	IF ret_code <> '' AND ret_expTime <= NOW() THEN
		SET ret_errcode = 4;
	END IF;

	#check voucher has been redeemed
	IF ret_code <> '' AND ret_status != 0 THEN
		SET ret_errcode = 5;
	END IF;

	IF ret_errcode = 0 AND a_redeem = 1 THEN		
		#set voucher as redeemed
		UPDATE vouchers SET `status`=1, `use_time`=NOW() WHERE`code`=a_code;
	END IF;
	
	IF ret_errcode = 0 AND a_redeem = 3 THEN
		UPDATE vouchers SET `status`=3, `use_time`=NOW() WHERE`code`=a_code;
	END IF;
		
	SELECT 
		ret_code as `code`, 
		ret_points as `points`,
		ret_contentType as `contentType`,
		ret_voucherType as `voucherType`,
		NOW() as `useTime`,
		ret_shopId as `shopId`,  
		ret_pointsStartTime as `pointsStartTime`, 
		ret_pointsExpTime as `pointsExpTime`,
		ret_pass AS `passId`, 
		ret_errCode as `errCode`; 
		
END



CREATE DEFINER=`tfc_import`@`%` PROCEDURE `updateSfVoucher`(
	IN `a_code` VARCHAR(255),
	IN `a_shop_id` INT,
	IN `a_user` VARCHAR(255),
	IN `a_sf_voucher` VARCHAR(255)
)
LANGUAGE SQL
NOT DETERMINISTIC
CONTAINS SQL
SQL SECURITY DEFINER
COMMENT ''
BEGIN

	DECLARE ret_errcode INT;
	DECLARE ret_code VARCHAR(255);
	DECLARE ret_points INT;
	DECLARE ret_contentType INT;
	DECLARE ret_voucherType INT;
	DECLARE ret_shopId INT;
	DECLARE ret_startTime DATETIME;
	DECLARE ret_expTime DATETIME;
	DECLARE ret_pointsStartTime DATETIME;
	DECLARE ret_pointsExpTime DATETIME;
	DECLARE ret_status INT;
	DECLARE ret_wallet INT;
	DECLARE ret_pass VARCHAR(255);
	DECLARE ret_user_id VARCHAR(255);
	DECLARE ret_sfvoucher VARCHAR(255);

	SET ret_errcode = 0;
	SET ret_code = '';
	SET ret_points = 0;
	SET ret_contentType = 0;
	SET ret_voucherType = 0;
	SET ret_shopId = 0;
	SET ret_startTime = NOW();
	SET ret_expTime = NOW();
	SET ret_pointsStartTime = NOW();
	SET ret_pointsExpTime = NOW();
	SET ret_status = 0;
	SET ret_wallet = 0;
	SET ret_user_id = '';
	SET ret_sfvoucher = '';
	SET ret_pass = '';
	
	SELECT 
		`code`, `points`, `content_type_id`, `voucher_type`, `shop_id`, `start_time`, `exp_time`, `points_start_time`, `points_exp_time`, `status`, `pass_id` 
	INTO ret_code, ret_points, ret_contentType, ret_voucherType, ret_shopId, ret_startTime, ret_expTime, ret_pointsStartTime, ret_pointsExpTime, ret_status, ret_pass 
	FROM vouchers WHERE `code`=a_code;
		
	#check voucher exists
	IF ret_code = '' THEN
		SET ret_errcode = 1;
	END IF;
	
	#check shop id is correct
	IF ret_code <> '' AND ret_shopId != a_shopId THEN
		SET ret_errcode = 2;
	END IF;

	#check voucher has started
	IF ret_code <> '' AND ret_startTime > NOW() THEN
		SET ret_errcode = 3;
	END IF;

	#check voucher is not expired
	IF ret_code <> '' AND ret_expTime <= NOW() THEN
		SET ret_errcode = 4;
	END IF;

	#check voucher has been redeemed
	IF ret_code <> '' AND ret_status != 0 THEN
		SET ret_errcode = 5;
	END IF;

	IF ret_errcode = 0 THEN		
		#set voucher as redeemed
		UPDATE vouchers SET `user_id`=a_user, `sfvoucher`=a_sf_voucher WHERE`code`=a_code AND `shop_id`=a_shop_id;
	END IF;
	
	SELECT 
		ret_code as `code`, 
		ret_points as `points`,
		ret_contentType as `contentType`,
		ret_voucherType as `voucherType`,
		NOW() as `useTime`,
		ret_shopId as `shopId`,  
		ret_pointsStartTime as `pointsStartTime`, 
		ret_pointsExpTime as `pointsExpTime`,
		ret_pass AS `passId`,
		ret_wallet AS `wallet`,
		ret_user_id AS `user_id`,
		ret_sfvoucher AS `sfvoucher`,
		ret_errCode as `errCode`; 
		
END