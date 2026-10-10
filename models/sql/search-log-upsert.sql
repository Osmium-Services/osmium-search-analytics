-- Log search term (upsert: insert or increment count)
INSERT INTO {TABLE} (search_term, search_date, search_count, result_count)
VALUES (:term, CURDATE(), 1, :results)
ON DUPLICATE KEY UPDATE search_count = search_count + 1, result_count = :results
