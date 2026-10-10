<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Models;

use Osmium\Core\Library\OsmiumPDO;
use Osmium\Core\Models\Model;

/**
 * The search log: one row per term per day with how many times it was searched and how many results it
 * found. Holds no visitor details.
 */
class SearchLog extends Model
{
    protected ?string $sqlDir = __DIR__ . '/sql';

    public function __construct(OsmiumPDO $database)
    {
        parent::__construct(database: $database, tableName: 'search_logs');
    }

    /** Count a search (the term already normalised) */
    public function record(string $term, int $resultCount): void
    {
        $this->database->prepare(
            sql: $this->loadSqlFile('search-log-upsert.sql'),
            bindings: [':term' => $term, ':results' => $resultCount],
        )->execute();
    }

    /** @return array<string, mixed> total_searches, unique_terms and failed_searches for the last 30 days */
    public function stats(): array
    {
        $row = $this->database->prepare(sql: $this->loadSqlFile('search-logs-stats.sql'), bindings: [])->single();

        return $row ?: [];
    }

    /** @return array<int, array<string, mixed>> The 50 most searched terms of the last 30 days */
    public function top(): array
    {
        return $this->many('search-logs-top.sql');
    }

    /** @return array<int, array<string, mixed>> The 50 most searched terms of the last 30 days that found nothing */
    public function failed(): array
    {
        return $this->many('search-logs-failed.sql');
    }

    /** @return string[] */
    public function brandNames(): array
    {
        return \array_column($this->many('distinct-brands.sql'), 'name');
    }

    /** @return string[] */
    public function modelNames(): array
    {
        return \array_column($this->many('distinct-models.sql'), 'model_name');
    }

    private function many(string $file): array
    {
        return $this->database->prepare(sql: $this->loadSqlFile($file), bindings: [])->resultset();
    }
}
