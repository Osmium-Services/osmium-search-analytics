SELECT
    search_term,
    SUM(search_count) as total_searches,
    MAX(result_count) as results
FROM {TABLE}
WHERE search_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY search_term
ORDER BY total_searches DESC, search_term ASC
LIMIT 50
