SELECT
    DISTINCT name
FROM
    {PREFIX}productbrands
WHERE
    deleted_at IS NULL
