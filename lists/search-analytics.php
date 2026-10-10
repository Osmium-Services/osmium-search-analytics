<?php

declare(strict_types=1);

/**
 * Search Analytics list definitions, rendered by
 * Osmium\Modules\Admin\Core\ListView. Read-only report tables: 'top' has no
 * actions; 'failed' has one event action (Add synonym) handled by
 * search-analytics.js. Row fields are prepared by SearchTerms.
 */
return [
    'top' => [
        'id' => 'search-top',
        'title' => 'Top Searches',
        'icon' => 'bx-trending-up',
        'iconClass' => 'text-success',
        'note' => 'Last 30 days',
        'noun' => ['search', 'searches'],
        'datatable' => false,
        'emptyText' => 'No searches yet',
        'columns' => [
            ['key' => 'search_term', 'label' => 'Search Term', 'type' => 'text', 'code' => true, 'fill' => true],
            ['key' => 'total_searches_formatted', 'label' => 'Count', 'type' => 'text', 'align' => 'end'],
            ['key' => 'resultsBadge', 'label' => 'Results', 'type' => 'raw', 'align' => 'end'],
        ],
        'actions' => [],
    ],

    'failed' => [
        'id' => 'search-failed',
        'title' => 'Failed Searches',
        'icon' => 'bx-x-circle',
        'iconClass' => 'text-danger',
        'note' => 'No results returned',
        'noun' => ['search', 'searches'],
        'datatable' => false,
        'emptyText' => 'No failed searches yet',
        'rowData' => ['search_term', 'suggestion'],
        'columns' => [
            ['key' => 'search_term', 'label' => 'Search Term', 'type' => 'text', 'code' => true, 'fill' => true],
            ['key' => 'total_searches_formatted', 'label' => 'Count', 'type' => 'text', 'align' => 'end'],
            ['key' => 'lastSearchedFormatted', 'label' => 'Last Searched', 'type' => 'text'],
        ],
        'actions' => [
            [
                'label' => 'Add synonym',
                'icon' => 'bx-link',
                'event' => 'add-synonym',
                'visible' => fn(array $row): bool => (bool) $row['hasSuggestion'],
            ],
        ],
    ],
];
