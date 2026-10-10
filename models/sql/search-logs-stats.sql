SELECT
    SUM(search_count) as total_searches,
    COUNT(DISTINCT search_term) as unique_terms,
    SUM(CASE WHEN result_count = 0 THEN search_count ELSE 0 END) as failed_searches
FROM {TABLE}
WHERE search_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
