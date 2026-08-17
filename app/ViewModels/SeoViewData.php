<?php

declare(strict_types=1);

namespace App\ViewModels;

final class SeoViewData
{
    /** @return array<string, mixed> */
    public static function create(array $config, bool $isSearchResult): array
    {
        $app = $config['app'] ?? [];
        $seo = $config['seo'] ?? [];
        $name = (string)($app['name'] ?? 'Travel Compass');
        $url = self::httpsUrl((string)($seo['canonical_url'] ?? ''));
        $image = self::httpsUrl((string)($seo['og_image_url'] ?? ''));
        $title = (string)($seo['title'] ?? $name);
        $description = (string)($seo['description'] ?? '');

        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => $url . '#website',
                    'name' => $name,
                    'url' => $url,
                    'inLanguage' => 'ja',
                ],
                [
                    '@type' => 'WebApplication',
                    '@id' => $url . '#webapplication',
                    'name' => $name,
                    'url' => $url,
                    'description' => $description,
                    'applicationCategory' => 'TravelApplication',
                    'operatingSystem' => 'Web browser',
                    'browserRequirements' => 'Requires JavaScript',
                    'inLanguage' => 'ja',
                    'isPartOf' => ['@id' => $url . '#website'],
                    'featureList' => [
                        '航空券検索・予約サイト比較',
                        'ホテル検索・予約サイト比較',
                        '最近の航空券検索履歴',
                    ],
                ],
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonicalUrl' => $url,
            'robots' => $isSearchResult ? 'noindex, follow' : 'index, follow',
            'ogType' => 'website',
            'ogImageUrl' => $image,
            'twitterCard' => (string)($seo['twitter_card'] ?? 'summary_large_image'),
            'structuredData' => json_encode(
                $structuredData,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_THROW_ON_ERROR
            ),
        ];
    }

    private static function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')
            ? $url
            : 'https://hirokazu-watabe.jp/travel-compass/';
    }
}
