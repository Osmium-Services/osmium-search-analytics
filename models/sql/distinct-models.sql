SELECT
    DISTINCT model_name
FROM
    {PREFIX}products
WHERE
    deleted_at IS NULL
