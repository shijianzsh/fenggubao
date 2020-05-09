
SET @loginname = '13881860856';

SELECT
	p.id AS user_id,
	p.loginname,
	p.truename,
	p.`level`,
	FROM_UNIXTIME(p.open_time)
FROM
	zc_member AS m
LEFT JOIN zc_member AS p ON FIND_IN_SET(p.id, m.repath)
WHERE
	m.loginname = @loginname;

SELECT
	c.id AS user_id,
	c.loginname,
	c.truename,
	c.`level`,
	FROM_UNIXTIME(c.open_time)
FROM
	zc_member AS m
LEFT JOIN zc_member AS c ON FIND_IN_SET(m.id, c.repath)
WHERE
	m.loginname = @loginname;

SELECT
	m.id AS user_id,
	m.loginname,
	m.truename,
	m.`level`,
	FROM_UNIXTIME(m.open_time),
	o.id,
	o.uid,
	o.amount,
	o.order_status,
	FROM_UNIXTIME(o.pay_time)
FROM
	zc_orders AS o
LEFT JOIN zc_member AS m ON o.uid = m.id
WHERE
	-- 	o.order_status NOT IN (0, 2)
	-- AND 
	m.loginname = @loginname
ORDER BY
	o.id DESC;

SELECT
	*, FROM_UNIXTIME(record_addtime)
FROM
	zc_account_goldcoin_201901
WHERE
	record_attach LIKE '%"order_id":"2242"%';