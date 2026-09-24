<?php

namespace App\ViewModels;

use App\Models\SearchHistory;

final class SearchPageBuilder implements SearchPageBuilderInterface
{
    public function __construct(private SearchHistory $searchHistory, private array $config, private string $visitorId) {}

    public function build(
        FlightSearchViewData|HotelSearchViewData|FerrySearchViewData|null $state,
        bool $isPost,
        string $csrfToken
    ): SearchPageViewModel {
        $appVersion = $this->config['app']['version'] ?? '1.9.3';
        $publicPath = dirname(__DIR__, 2) . '/public/assets/';
        return new SearchPageViewModel(
            flight: $state instanceof FlightSearchViewData ? $state : new FlightSearchViewData(),
            hotel: $state instanceof HotelSearchViewData ? $state : new HotelSearchViewData(),
            ferry: $state instanceof FerrySearchViewData ? $state : new FerrySearchViewData(),
            activeTab: match (true) {
                $state instanceof HotelSearchViewData => 'hotel',
                $state instanceof FerrySearchViewData => 'ferry',
                default => 'flight',
            },
            isSearchResult: $isPost,
            csrfToken: $csrfToken,
            recent: $this->recentSearches(),
            appName: $this->config['app']['name'] ?? 'Travel Compass',
            appVersion: $appVersion,
            cssVersion: (string)(filemtime($publicPath . 'app.css') ?: $appVersion),
            ferryMapCssVersion: (string)(filemtime($publicPath . 'ferry-map.css') ?: $appVersion),
            jsVersion: ScriptAssetVersion::create($publicPath),
            seo: SeoViewData::create($this->config, $isPost)
        );
    }

    private function recentSearches(): array
    {
        try {
            return $this->searchHistory->recent($this->visitorId);
        } catch (\Throwable $e) {
            \App\Core\RequestLog::failure('history.read', $e);
            return [];
        }
    }

}
