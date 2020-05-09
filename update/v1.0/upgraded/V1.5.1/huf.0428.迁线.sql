
SET @p_loginname = 14780188391;


SET @c_loginname = 15378692798;

-- 
SELECT
  m.id,
  m.truename,
  m.reid,
  m.relevel,
  m.repath INTO @p_id,
    @p_truename,
    @p_reid,
    @p_relevel,
    @p_repath
FROM
  zc_member AS m
WHERE
    m.loginname = @p_loginname;

SELECT
  @p_id,
  @p_truename,
  @p_reid,
  @p_relevel,
  @p_repath;

SELECT
  m.id,
  m.truename,
  m.reid,
  m.relevel,
  m.repath INTO @c_id,
    @c_truename,
    @c_reid,
    @c_relevel,
    @c_repath
FROM
  zc_member AS m
WHERE
    m.loginname = @c_loginname;

SELECT
  @c_id,
  @c_truename,
  @c_reid,
  @c_relevel,
  @c_repath;


SET @n_relevel = @p_relevel + 1,
  @n_repath = CONCAT(@p_repath, @p_id, ',');

SELECT
  @n_relevel,
  @n_repath;

SELECT
  m.id,
  m.truename,
  m.reid,
  m.relevel,
  m.repath
FROM
  zc_member AS m
WHERE
    m.id = @c_id
   OR FIND_IN_SET(@c_id, m.repath);

UPDATE zc_member AS m
SET m.relevel = m.relevel + (@n_relevel - @c_relevel),
    m.repath = REPLACE (
        m.repath,
        @c_repath,
        @n_repath
      )
WHERE
  FIND_IN_SET(@c_id, m.repath);

UPDATE zc_member AS m
SET m.relevel = @n_relevel,
    m.repath = @n_repath,
    m.reid = @p_id
WHERE
    m.id = @c_id;

SELECT
  m.id,
  m.truename,
  m.reid,
  m.relevel,
  m.repath
FROM
  zc_member AS m
WHERE
    m.id = @c_id
   OR FIND_IN_SET(@c_id, m.repath);