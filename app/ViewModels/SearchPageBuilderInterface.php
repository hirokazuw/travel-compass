<?php

declare(strict_types=1);

namespace App\ViewModels;

interface SearchPageBuilderInterface
{
    public function build(
        FlightSearchViewData|HotelSearchViewData|FerrySearchViewData|null $state,
        bool $isPost,
        string $csrfToken
    ): SearchPageViewModel;
}
