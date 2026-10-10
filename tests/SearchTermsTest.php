<?php

declare(strict_types=1);

namespace Tests\Services\SearchAnalytics;

use Osmium\Services\SearchAnalytics\Models\SearchTerms;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/SearchTerms.php';

/**
 * How terms are stored, which failed searches still need a synonym, and the suggestion for each.
 */
final class SearchTermsTest extends TestCase
{
    public function test_a_logged_term_is_trimmed_lowercased_and_cut_to_100_characters(): void
    {
        $this->assertSame('mppt charger', SearchTerms::normalize('  MPPT Charger '));
        $this->assertSame(\str_repeat('a', 100), SearchTerms::normalize(\str_repeat('A', 150)));
        $this->assertSame('', SearchTerms::normalize('   '));
    }

    public function test_suggestion_terms_merge_synonym_keys_brands_and_models_in_lower_case_without_repeats(): void
    {
        $terms = SearchTerms::suggestionTerms(['victron'], ['Victron', 'Renogy'], ['MPPT 100']);

        $this->assertSame(['victron', 'renogy', 'mppt 100'], $terms);
    }

    public function test_failed_searches_that_already_have_a_synonym_are_left_out(): void
    {
        $searches = [['search_term' => 'victorn'], ['search_term' => 'renogy']];

        $this->assertSame([['search_term' => 'renogy']], SearchTerms::withoutSynonyms($searches, ['victorn' => ['victron']]));
    }

    public function test_a_term_within_three_typos_gets_that_suggestion(): void
    {
        $this->assertSame('victron', SearchTerms::findSuggestion('victorn', ['renogy', 'victron']));
    }

    public function test_a_term_gets_no_suggestion_when_nothing_is_close_or_it_is_the_term_itself(): void
    {
        $this->assertNull(SearchTerms::findSuggestion('victron', ['victron']));
        $this->assertNull(SearchTerms::findSuggestion('inverter', ['renogy']));
    }

    public function test_with_suggestions_adds_a_suggestion_to_every_search(): void
    {
        $searches = SearchTerms::withSuggestions([['search_term' => 'victorn'], ['search_term' => 'zzzzzz']], ['victron']);

        $this->assertSame('victron', $searches[0]['suggestion']);
        $this->assertNull($searches[1]['suggestion']);
    }

    public function test_a_top_search_with_no_results_gets_a_red_zero_badge(): void
    {
        $zero = SearchTerms::formatTop(['search_term' => 'x', 'total_searches' => '1200', 'results' => '0']);
        $some = SearchTerms::formatTop(['search_term' => 'y', 'total_searches' => '3', 'results' => '4']);

        $this->assertSame('1,200', $zero['total_searches_formatted']);
        $this->assertStringContainsString('bg-danger', $zero['resultsBadge']);
        $this->assertSame('4', $some['resultsBadge']);
    }

    public function test_a_failed_search_is_formatted_with_its_date_and_suggestion_flag(): void
    {
        $row = SearchTerms::formatFailed(['search_term' => 'x', 'total_searches' => '5', 'last_searched' => '2026-10-03', 'suggestion' => 'victron']);

        $this->assertSame('3 Oct', $row['lastSearchedFormatted']);
        $this->assertTrue($row['hasSuggestion']);
        $this->assertFalse(SearchTerms::formatFailed(['search_term' => 'x', 'total_searches' => 1, 'last_searched' => '2026-10-03'])['hasSuggestion']);
    }
}
