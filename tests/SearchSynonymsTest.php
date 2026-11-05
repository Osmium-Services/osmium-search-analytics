<?php

declare(strict_types=1);

namespace Tests\Services\SearchAnalytics;

use Osmium\Services\SearchAnalytics\Models\SearchHooks;
use Osmium\Services\SearchAnalytics\Models\SearchSynonyms;
use Tests\Integration\DatabaseTestCase;

require_once __DIR__ . '/../models/SearchSynonyms.php';
require_once __DIR__ . '/../models/SearchTerms.php';
require_once __DIR__ . '/../models/SearchLog.php';
require_once __DIR__ . '/../models/SearchHooks.php';

/**
 * Adding a synonym for the shop's search, run against real rows, and the search.synonyms hook that hands them to the shop.
 */
final class SearchSynonymsTest extends DatabaseTestCase
{
    private SearchSynonyms $synonyms;
    private string $runTag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->synonyms = new SearchSynonyms($this->db);
        $this->runTag = 'zq' . \substr(\uniqid(), -6);

        $prefix = $this->db->tablePrefix();
        $tableExists = $this->db->prepare("SHOW TABLES LIKE '{$prefix}search_synonyms'")->single() !== false;
        if (!$tableExists) $this->markTestSkipped('The Search Analytics synonyms table is not installed');
    }

    private function term(string $suffix): string
    {
        return "{$this->runTag}-{$suffix}";
    }

    public function test_a_synonym_is_added_to_a_new_term_lower_cased_and_reads_back(): void
    {
        $this->assertNull($this->synonyms->add(' ' . \strtoupper($this->term('victorn')) . ' ', 'VICTRON'));

        $this->assertSame(['victron'], $this->synonyms->all()[$this->term('victorn')]);
    }

    public function test_a_second_target_is_added_to_an_existing_term_and_a_repeat_is_not(): void
    {
        $term = $this->term('solar');

        $this->synonyms->add($term, 'pv');
        $this->synonyms->add($term, 'photovoltaic');
        $this->synonyms->add($term, 'photovoltaic');

        $this->assertSame(['pv', 'photovoltaic'], $this->synonyms->all()[$term]);
    }

    public function test_an_empty_or_identical_pair_is_refused_and_nothing_is_stored(): void
    {
        $term = $this->term('same');

        $this->assertSame('Both term and target are required', $this->synonyms->add($term, ' '));
        $this->assertSame('Term and target cannot be the same', $this->synonyms->add(\strtoupper($term), $term));
        $this->assertArrayNotHasKey($term, $this->synonyms->all());
    }

    public function test_quotes_in_a_term_are_stored_as_text(): void
    {
        $term = $this->term("o'neil");

        $this->assertNull($this->synonyms->add($term, 'o"neil'));
        $this->assertSame(['o"neil'], $this->synonyms->all()[$term]);
    }

    public function test_the_search_synonyms_hook_returns_every_term_for_the_shop(): void
    {
        $term = $this->term('panel');
        $this->synonyms->add($term, 'module');

        $result = SearchHooks::synonyms(['dataSource' => $this->db]);

        $this->assertSame(['module'], $result[$term]);
    }
}
