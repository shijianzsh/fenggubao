# 18379330590	盛晓兵 补关爱奖 6.2489
SET @userId = 4926, @amount = 6.2489, @orderId = 4777;
CALL AddAccountRecord(@userId, 'goldcoin', 108, @amount, UNIX_TIMESTAMP(), concat('{"order_id":"', @orderId, '"}'), 'gaj', 50, @error);
SELECT @error;
CALL Income_add(@userId, @amount, @error);
SELECT @error;

#  13506807889	黄延英 补关爱奖1031.0696
SET @userId = 4928, @amount = 1031.0696, @orderId = 4788;
CALL AddAccountRecord(@userId, 'goldcoin', 108, @amount, UNIX_TIMESTAMP(), concat('{"order_id":"', @orderId, '"}'), 'gaj', 50, @error);
SELECT @error;
CALL Income_add(@userId, @amount, @error);
SELECT @error;


