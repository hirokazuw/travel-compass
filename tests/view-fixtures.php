<?php

function viewFixtures(): array
{
    $flightValues = ['origin' => '<Tokyo>', 'destination' => '大阪', 'departure_date' => '2026-10-01', 'return_date' => '', 'travelers' => '2'];
    $hotelValues = ['hotel_destination' => '<Paris>', 'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-03', 'hotel_adults' => '2', 'hotel_children' => '1'];
    $links = array_fill_keys(['maps', 'expedia', 'agoda', 'airtrip', 'travelist', 'realticket', 'skygate', 'jtb', 'skyticket'], 'https://example.com/?a=1&b=2');
    $offer = ['carrier_code' => 'JL', 'airline_logo' => '', 'official_url' => 'https://example.com/', 'carrier_name' => '<Airline>', 'alliance' => 'oneworld', 'ffp_name' => 'Miles', 'flight_count' => 2, 'direct_flight_count' => 1, 'currency' => 'JPY', 'price' => '12,000'];
    $hotel = ['name' => '<Hotel>', 'image_urls' => ['https://example.com/a.jpg', 'https://example.com/b.jpg'], 'hotel_class' => '5', 'rating' => 4.5, 'reviews' => 100, 'description' => '<Description>', 'address' => 'Paris', 'amenities' => ['Wi-Fi'], 'check_in_time' => '15:00', 'check_out_time' => '11:00', 'official_url' => 'https://example.com/', 'booking_links' => $links, 'price_per_night' => 12000, 'total_price' => 24000];
    $route = ['company_name' => '<Ferry>', 'destination_url' => 'https://example.com/', 'route_name' => '東京〜徳島', 'departure_port' => '東京', 'arrival_port' => '徳島', 'duration' => '10時間', 'vehicle_available' => true, 'overnight' => true, 'fare_from' => '12,000', 'fare_currency' => 'JPY', 'fare_updated' => '2026-09-01'];
    return [
        'initial' => [false, []],
        'unknown-post' => [true, []],
        'flight-invalid' => [true, ['values' => $flightValues, 'errors' => ['<Error>']]],
        'flight-domestic' => [true, ['values' => $flightValues, 'result' => $links, 'isDomesticFlight' => true, 'flightOffersStatus' => 'success', 'flightOffers' => array_fill(0, 7, $offer)]],
        'flight-overseas-error' => [true, ['values' => $flightValues, 'result' => $links, 'activeFlightScope' => 'overseas', 'flightOffersStatus' => 'error']],
        'hotel-invalid' => [true, ['activeTab' => 'hotel', 'hotelValues' => $hotelValues, 'activeHotelScope' => 'korea', 'hotelErrors' => ['<Error>']]],
        'hotel-success' => [true, ['activeTab' => 'hotel', 'hotelValues' => $hotelValues, 'activeHotelScope' => 'overseas', 'hotelStatus' => 'success', 'hotels' => array_fill(0, 7, $hotel), 'rakutenHotelLinks' => [0 => $links['agoda']], 'hotelOtaGuide' => ['show_guide' => true, 'message' => '<Guide>', 'url' => $links['agoda'], 'label' => 'Book', 'hidden_booking_links' => ['expedia']]]],
        'hotel-unavailable' => [true, ['activeTab' => 'hotel', 'hotelStatus' => 'not_configured']],
        'ferry-invalid' => [true, ['activeTab' => 'ferry', 'ferryErrors' => ['<Error>'], 'ferryStatus' => 'invalid']],
        'ferry-success' => [true, ['activeTab' => 'ferry', 'ferryStatus' => 'success', 'ferryRoutes' => [$route]]],
        'ferry-empty' => [true, ['activeTab' => 'ferry', 'ferryStatus' => 'empty']],
        'ferry-error' => [true, ['activeTab' => 'ferry', 'ferryStatus' => 'error']],
    ];
}
