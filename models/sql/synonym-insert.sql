-- Map a term to a target (a pair already stored is left alone)
INSERT IGNORE INTO {TABLE} (term, target)
VALUES (:term, :target)
