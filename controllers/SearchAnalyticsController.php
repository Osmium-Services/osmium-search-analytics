<?php

declare(strict_types=1);

namespace Osmium\Services\SearchAnalytics\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\SearchAnalytics\Models\SearchLog;
use Osmium\Services\SearchAnalytics\Models\SearchTerms;
use Osmium\Services\SearchAnalytics\Models\SynonymFile;

/**
 * Search analytics: what customers searched for in the last 30 days, the searches that found nothing,
 * and adding a synonym for one of those.
 *
 * Routes:
 *   - index()  → /admin/search-analytics/
 *   - action() → /admin/search-analytics/action/   (add_synonym)
 */
class SearchAnalyticsController extends AdminController
{
    public function index(): void
    {
        $log = new SearchLog($this->osmium->dataSource);
        $synonymFile = new SynonymFile();
        $synonyms = $synonymFile->load();

        $suggestionTerms = SearchTerms::suggestionTerms(\array_keys($synonyms), $log->brandNames(), $log->modelNames());
        $failed = SearchTerms::withSuggestions(SearchTerms::withoutSynonyms($log->failed(), $synonyms), $suggestionTerms);
        $stats = $log->stats();

        $this->data['admin']['lists'] = require __DIR__ . '/../lists/search-analytics.php';
        $this->data['admin']['topSearches'] = \array_map(SearchTerms::formatTop(...), $log->top());
        $this->data['admin']['failedSearches'] = \array_map(SearchTerms::formatFailed(...), $failed);
        $this->data['admin']['totalSearches'] = (int) ($stats['total_searches'] ?? 0);
        $this->data['admin']['uniqueTerms'] = (int) ($stats['unique_terms'] ?? 0);
        $this->data['admin']['failedCount'] = (int) ($stats['failed_searches'] ?? 0);
        $this->data['admin']['existingSynonyms'] = $synonyms;
        $this->data['admin']['suggestionTerms'] = \array_map('htmlspecialchars', $suggestionTerms);

        $this->setView('search-analytics/index.phtml');
    }

    public function action()
    {
        \header('Content-Type: application/json');

        $input = $this->admin->auth->getJsonInput();
        $csrfValid = $this->admin->auth->validateCsrfJson($input);
        if (!$csrfValid) $this->respond(['success' => false, 'error' => 'Invalid request token. Please refresh the page and try again.']);

        $action = $input['action'] ?? '';
        if ($action !== 'add_synonym') $this->respond(['success' => false, 'error' => 'Unknown action']);

        $term = \strtolower(\trim($input['term'] ?? ''));
        $target = \strtolower(\trim($input['target'] ?? ''));
        $error = (new SynonymFile())->add($term, $target);

        $this->respond($error === null ? ['success' => true, 'term' => $term, 'target' => $target] : ['success' => false, 'error' => $error]);
    }

    private function respond(array $data): never
    {
        echo \json_encode($data);
        exit;
    }
}
