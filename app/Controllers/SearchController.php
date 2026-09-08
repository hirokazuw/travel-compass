<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\SearchHtmlResponse;
use App\Http\JsonResponse;
use App\ViewModels\SearchPageBuilderInterface;

final class SearchController
{
    /**
     * @param array<string, callable(array, string): (\App\ViewModels\FlightSearchViewData|\App\ViewModels\HotelSearchViewData|\App\ViewModels\FerrySearchViewData)> $searchActions
     * @param array<string, callable(array, string): \App\Http\JsonResponse> $jsonActions
     */
    public function __construct(
        private array $searchActions,
        private array $jsonActions,
        private SearchPageBuilderInterface $pageBuilder
    ) {}

    public function index(): void
    {
        $response = $this->handle(
            (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST, (string)($_SESSION['csrf'] ?? '')
        );
        if ($response instanceof SearchHtmlResponse) {
            $_SESSION['csrf'] = $response->data->csrfToken;
        }
        $response->send();
    }

    public function handle(string $method, array $input, string $sessionToken): SearchHtmlResponse|JsonResponse
    {
        $isPost = $method === 'POST';
        $searchType = (string)($input['search_type'] ?? 'flight');
        if ($isPost && isset($this->jsonActions[$searchType])) {
            return ($this->jsonActions[$searchType])($input, $sessionToken);
        }
        $state = null;
        if ($isPost && isset($this->searchActions[$searchType])) {
            $state = ($this->searchActions[$searchType])($input, $sessionToken);
        }
        return new SearchHtmlResponse($this->pageBuilder->build($state, $isPost, bin2hex(random_bytes(32))));
    }
}
