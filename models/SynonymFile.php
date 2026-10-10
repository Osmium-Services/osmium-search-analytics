<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Models;

/**
 * The shop's search synonyms file (search-synonyms.php), which core search reads. A term maps to the
 * words it should also match.
 */
class SynonymFile
{
    public const PATH = 'app/modules/shop/config/search-synonyms.php';

    public function __construct(private string $path = self::PATH) {}

    /** @return array<string, string[]> */
    public function load(): array
    {
        return \file_exists($this->path) ? (require $this->path) : [];
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

        $synonyms = $this->load();
        $synonyms[$term] ??= [];
        if (!\in_array($target, $synonyms[$term])) $synonyms[$term][] = $target;

        return $this->write($synonyms);
    }

    /** The file's PHP source for these synonyms */
    public static function render(array $synonyms): string
    {
        $content = "<?php\n\n";
        $content .= "// Search synonym mappings\n";
        $content .= "// Format: 'search term' => ['synonym1', 'synonym2', ...]\n";
        $content .= "// These are expanded with OR logic\n\n";
        $content .= "return [\n";

        foreach ($synonyms as $key => $values) {
            $quoted = \array_map(static fn(string $value): string => "'" . \addslashes($value) . "'", $values);
            $content .= "    '" . \addslashes((string) $key) . "' => [" . \implode(', ', $quoted) . "],\n";
        }

        return $content . "];\n";
    }

    private function write(array $synonyms): ?string
    {
        $written = \file_put_contents($this->path, self::render($synonyms));
        if ($written === false) return 'Failed to write synonyms file';

        if (\function_exists('opcache_invalidate') && \realpath($this->path)) {
            \opcache_invalidate(\realpath($this->path), true);
        }

        return null;
    }
}
