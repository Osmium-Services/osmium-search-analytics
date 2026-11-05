<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Models;

/**
 * The handlers for the core hooks this service listens to.
 */
class SearchHooks
{
    /**
     * search.performed: count the search against its term for today.
     *
     * @param array{term: string, resultCount: int, dataSource: object} $payload
     */
    public static function searchPerformed(array $payload): void
    {
        $term = SearchTerms::normalize($payload['term']);
        if ($term === '') return;

        (new SearchLog($payload['dataSource']))->record($term, (int) $payload['resultCount']);
    }

    /**
     * search.synonyms: the shop asks for every term and the words it should also match.
     *
     * @param array{dataSource: object} $payload
     * @return array<string, string[]>|null null when there are none
     */
    public static function synonyms(array $payload): ?array
    {
        return (new SearchSynonyms($payload['dataSource']))->all() ?: null;
    }
}
