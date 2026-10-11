<?php

declare(strict_types=1);

namespace Tests\Services\SearchAnalytics;

use Osmium\Services\SearchAnalytics\Models\SearchLog;
use Tests\Integration\DatabaseTestCase;

require_once __DIR__ . '/../models/SearchLog.php';

/**
 * The SQL behind search_summary, run against real rows: only the last 30 days count, a repeated search adds to
 * one row, and failed means a search that found nothing.
 */
class SearchLogSqlTest extends DatabaseTestCase
{
    private SearchLog $log;
    private string $runTag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log = new SearchLog($this->db);
        $this->runTag = 'zq' . \substr(\uniqid(), -6);

        $prefix = $this->db->tablePrefix();
        $tableExists = $this->db->prepare("SHOW TABLES LIKE '{$prefix}search_logs'")->single() !== false;
        if (!$tableExists) $this->markTestSkipped('The Search Analytics table is not installed');
    }

    private function term(string $suffix): string
    {
        return "{$this->runTag}-{$suffix}";
    }

    private function row(string $suffix, int $daysAgo, int $count, int $results): void
    {
        $prefix = $this->db->tablePrefix();
        $this->db->prepare(
            "INSERT INTO {$prefix}search_logs (search_term, search_date, search_count, result_count) VALUES (:term, DATE_SUB(CURDATE(), INTERVAL :days DAY), :count, :results)",
            [':term' => $this->term($suffix), ':days' => $daysAgo, ':count' => $count, ':results' => $results],
        )->execute();
    }

    /** @return array<int, array<string, mixed>> this run's rows from a list, in the order returned */
    private function mine(array $rows): array
    {
        return \array_values(\array_filter($rows, fn(array $row): bool => \str_starts_with($row['search_term'], $this->runTag)));
    }

    public function test_a_repeated_search_on_one_day_adds_to_a_single_row_and_keeps_the_latest_result_count(): void
    {
        $this->log->record($this->term('again'), 3);
        $this->log->record($this->term('again'), 0);

        $rows = $this->mine($this->log->top());

        $this->assertCount(1, $rows);
        $this->assertSame(2, (int) $rows[0]['total_searches']);
        $this->assertSame(0, (int) $rows[0]['results']);
    }

    public function test_only_the_last_30_days_count_towards_the_totals(): void
    {
        $before = $this->log->stats();

        $this->row('recent', 1, 4, 5);
        $this->row('edge', 29, 2, 0);
        $this->row('old', 31, 100, 0);

        $after = $this->log->stats();

        $this->assertSame(6, (int) $after['total_searches'] - (int) $before['total_searches']);
        $this->assertSame(2, (int) $after['unique_terms'] - (int) $before['unique_terms']);
        $this->assertSame(2, (int) $after['failed_searches'] - (int) $before['failed_searches']);
    }

    public function test_a_term_searched_on_several_days_is_one_unique_term_with_the_days_added_up(): void
    {
        $before = $this->log->stats();

        $this->row('multi', 1, 3, 2);
        $this->row('multi', 2, 4, 2);

        $after = $this->log->stats();

        $this->assertSame(7, (int) $after['total_searches'] - (int) $before['total_searches']);
        $this->assertSame(1, (int) $after['unique_terms'] - (int) $before['unique_terms']);
        $this->assertSame(7, (int) $this->mine($this->log->top())[0]['total_searches']);
    }

    public function test_the_top_list_is_most_searched_first_and_leaves_out_old_rows(): void
    {
        $this->row('small', 1, 1000001, 3);
        $this->row('big', 1, 1000009, 3);
        $this->row('stale', 40, 1000050, 3);

        $terms = \array_column($this->mine($this->log->top()), 'search_term');

        $this->assertSame([$this->term('big'), $this->term('small')], $terms);
    }

    public function test_the_failed_list_has_only_searches_that_found_nothing_in_the_window(): void
    {
        $this->row('nothing', 1, 1000005, 0);
        $this->row('found', 1, 1000006, 8);
        $this->row('nothing-old', 45, 1000007, 0);

        $rows = $this->mine($this->log->failed());

        $this->assertSame([$this->term('nothing')], \array_column($rows, 'search_term'));
        $this->assertSame(1000005, (int) $rows[0]['total_searches']);
    }
}
