SELECT
    search_term,
    SUM(search_count) as total_searches,
    MAX(search_date) as last_searched
FROM {TABLE}
WHERE
    result_count = 0
    AND search_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY search_term
ORDER BY total_searches DESC, last_searched DESC
LIMIT 50
