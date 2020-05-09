SET @orderId = 4600;
CALL Refund(@orderId, @error);
SELECT @error;
CALL Income_again(@orderId, @error);
SELECT @error;

SET @orderId = 4738;
CALL Refund(@orderId, @error);
SELECT @error;
CALL Income_again(@orderId, @error);
SELECT @error;

SET @orderId = 4739;
CALL Refund(@orderId, @error);
SELECT @error;
CALL Income_again(@orderId, @error);
SELECT @error;
SET @orderId = 4742;
CALL Refund(@orderId, @error);
SELECT @error;
CALL Income_again(@orderId, @error);
SELECT @error;
