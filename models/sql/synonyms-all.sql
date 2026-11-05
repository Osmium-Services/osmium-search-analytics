-- Every synonym, oldest first within a term
SELECT term, target
FROM {TABLE}
ORDER BY term, id
