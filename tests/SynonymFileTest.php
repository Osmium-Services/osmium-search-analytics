<?php

declare(strict_types=1);

namespace Tests\Services\SearchAnalytics;

use Osmium\Services\SearchAnalytics\Models\SynonymFile;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/SynonymFile.php';

/**
 * Adding a synonym to the shop's search-synonyms file.
 */
final class SynonymFileTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = \sys_get_temp_dir() . '/synonyms-' . \uniqid() . '.php';
        \file_put_contents($this->path, "<?php\nreturn ['solar' => ['pv']];\n");
    }

    protected function tearDown(): void
    {
        @\unlink($this->path);
    }

    public function test_a_synonym_is_added_to_a_new_term_and_the_file_reads_back(): void
    {
        $file = new SynonymFile($this->path);

        $this->assertNull($file->add(' Victorn ', 'VICTRON'));
        $this->assertSame(['solar' => ['pv'], 'victorn' => ['victron']], $file->load());
    }

    public function test_a_second_target_is_added_to_an_existing_term_and_a_repeat_is_not(): void
    {
        $file = new SynonymFile($this->path);

        $file->add('solar', 'photovoltaic');
        $file->add('solar', 'photovoltaic');

        $this->assertSame(['solar' => ['pv', 'photovoltaic']], $file->load());
    }

    public function test_an_empty_or_identical_pair_is_refused_and_nothing_is_written(): void
    {
        $file = new SynonymFile($this->path);

        $this->assertSame('Both term and target are required', $file->add('solar', ' '));
        $this->assertSame('Term and target cannot be the same', $file->add('Solar', 'solar'));
        $this->assertSame(['solar' => ['pv']], $file->load());
    }

    public function test_quotes_in_a_term_are_escaped_so_the_file_stays_valid_php(): void
    {
        $file = new SynonymFile($this->path);

        $file->add("o'neil", "o'neill");

        $this->assertSame(["o'neil" => ["o'neill"]], \array_diff_key($file->load(), ['solar' => 1]));
    }

    public function test_a_missing_file_loads_as_no_synonyms(): void
    {
        $this->assertSame([], (new SynonymFile('/nonexistent/synonyms.php'))->load());
    }
}
