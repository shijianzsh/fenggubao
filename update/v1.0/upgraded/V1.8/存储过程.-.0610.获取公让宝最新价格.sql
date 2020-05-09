-- -------------------------------
-- 获取公让宝最新价格
-- -------------------------------
DROP FUNCTION IF EXISTS `GetGoldcoinLatestPrice`;
DELIMITER ;;
CREATE FUNCTION `GetGoldcoinLatestPrice`()
    RETURNS DECIMAL(14, 4)
BEGIN

    SET @goldcoinPrice = 1;
    SELECT amount INTO @goldcoinPrice
    FROM zc_goldcoin_prices
    ORDER BY id DESC
    LIMIT 1;
    RETURN @goldcoinPrice;
END
;;
DELIMITER ;