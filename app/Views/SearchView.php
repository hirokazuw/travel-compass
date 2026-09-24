<?php

declare(strict_types=1);

namespace App\Views;

use App\ViewModels\SearchPageViewModel;

final class SearchView
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function render(SearchPageViewModel $page, string $template = 'index'): string
    {
        if (!in_array($template, [
            'index', 'partials/search-panel', 'partials/flight-search-form',
            'partials/ferry-search-form', 'partials/flight-results',
            'partials/hotel-results', 'partials/ferry-results',
            'partials/recent-searches', 'partials/search-loading', 'partials/service-guide',
        ], true)) {
            throw new \InvalidArgumentException('Unknown search template: ' . $template);
        }
        ob_start();
        try {
            // Each partial receives only its explicit page model, never its caller's locals.
            require __DIR__ . '/search/' . $template . '.php';
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
