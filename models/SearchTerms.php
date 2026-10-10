<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Models;

/**
 * The logic behind the Search Analytics page: how a term is stored, which failed searches still need a
 * synonym, the spelling suggestion for each, and the rows as the lists show them. Free of the database.
 */
class SearchTerms
{
    public const SUGGESTION_DISTANCE = 3;

    /** A term as it is logged: trimmed, lower case, at most 100 characters */
    public static function normalize(string $term): string
    {
        return \strtolower(\substr(\trim($term), 0, 100));
    }

    /**
     * Terms a failed search can be pointed at: the existing synonym keys, brand names and model names.
     *
     * @param string[] $existingTerms
     * @param string[] $brands
     * @param string[] $models
     * @return string[]
     */
    public static function suggestionTerms(array $existingTerms, array $brands, array $models): array
    {
        $lower = static fn(array $names): array => \array_map('strtolower', $names);

        return \array_values(\array_unique(\array_merge($existingTerms, $lower($brands), $lower($models))));
    }

    /**
     * @param array<int, array<string, mixed>> $searches
     * @param array<string, string[]> $synonyms
     * @return array<int, array<string, mixed>> The searches that have no synonym yet
     */
    public static function withoutSynonyms(array $searches, array $synonyms): array
    {
        return \array_values(\array_filter($searches, static fn(array $search): bool => !isset($synonyms[$search['search_term']])));
    }

    /** The first suggestion within a few typos of the term (and not the term itself), or null */
    public static function findSuggestion(string $term, array $suggestionTerms): ?string
    {
        $termLength = \strlen($term);

        foreach ($suggestionTerms as $suggestion) {
            if (\abs(\strlen($suggestion) - $termLength) > self::SUGGESTION_DISTANCE) continue;
            if ($suggestion === $term) continue;
            if (\levenshtein($term, $suggestion) <= self::SUGGESTION_DISTANCE) return $suggestion;
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $searches
     * @return array<int, array<string, mixed>> The searches with a 'suggestion' (or null) added
     */
    public static function withSuggestions(array $searches, array $suggestionTerms): array
    {
        foreach ($searches as &$search) {
            $search['suggestion'] = self::findSuggestion($search['search_term'], $suggestionTerms);
        }

        return $searches;
    }

    /** @return array<string, mixed> */
    public static function formatTop(array $search): array
    {
        $results = (int) $search['results'];

        return [
            'search_term' => $search['search_term'],
            'total_searches' => (int) $search['total_searches'],
            'total_searches_formatted' => \number_format((int) $search['total_searches']),
            'results' => $results,
            'resultsBadge' => $results === 0 ? '<span class="badge bg-danger">0</span>' : (string) $results,
        ];
    }

    /** @return array<string, mixed> */
    public static function formatFailed(array $search): array
    {
        return [
            'search_term' => $search['search_term'],
            'total_searches' => (int) $search['total_searches'],
            'total_searches_formatted' => \number_format((int) $search['total_searches']),
            'last_searched' => $search['last_searched'],
            'lastSearchedFormatted' => \date('j M', \strtotime($search['last_searched'])),
            'suggestion' => $search['suggestion'] ?? null,
            'hasSuggestion' => !empty($search['suggestion']),
        ];
    }
}
