<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Models;

use Osmium\Core\Library\OsmiumPDO;
use Osmium\Core\Models\Model;

/**
 * The shop's search synonyms: a term mapped to the words it should also match. The shop reads them through the
 * `search.synonyms` hook, so they only apply while this service is installed.
 */
class SearchSynonyms extends Model
{
    protected ?string $sqlDir = __DIR__ . '/sql';

    public function __construct(OsmiumPDO $database)
    {
        parent::__construct(database: $database, tableName: 'search_synonyms');
    }

    /** @return array<string, string[]> */
    public function all(): array
    {
        $synonyms = [];
        foreach ($this->database->prepare(sql: $this->loadSqlFile('synonyms-all.sql'), bindings: [])->resultset() as $row) {
            $synonyms[$row['term']][] = $row['target'];
        }

        return $synonyms;
    }

    /**
     * Map a term to a target. Both are lower-cased; a target already mapped is not added twice.
     *
     * @return string|null An error message, or null when saved
     */
    public function add(string $term, string $target): ?string
    {
        $term = \strtolower(\trim($term));
        $target = \strtolower(\trim($target));

        if ($term === '' || $target === '') return 'Both term and target are required';
        if ($term === $target) return 'Term and target cannot be the same';

        $this->database->prepare(
            sql: $this->loadSqlFile('synonym-insert.sql'),
            bindings: [':term' => $term, ':target' => $target],
        )->execute();

        return null;
    }
}
