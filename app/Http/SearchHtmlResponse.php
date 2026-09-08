<?php

declare(strict_types=1);

namespace App\Http;

use App\ViewModels\SearchPageViewModel;
use App\Views\SearchView;

final class SearchHtmlResponse
{
    public function __construct(public readonly SearchPageViewModel $data) {}

    public function send(): void
    {
        if ($this->data->isSearchResult) {
            header('X-Robots-Tag: noindex, follow');
        }
        echo SearchView::render($this->data);
    }
}
