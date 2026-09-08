<?php

final class FakeSearchPageBuilder implements App\ViewModels\SearchPageBuilderInterface
{
    public array $calls = [];

    public function build(
        App\ViewModels\FlightSearchViewData|App\ViewModels\HotelSearchViewData|App\ViewModels\FerrySearchViewData|null $state,
        bool $isPost,
        string $csrfToken
    ): App\ViewModels\SearchPageViewModel {
        $this->calls[] = [$state, $isPost, $csrfToken];
        return new App\ViewModels\SearchPageViewModel(
            new App\ViewModels\FlightSearchViewData(), new App\ViewModels\HotelSearchViewData(),
            new App\ViewModels\FerrySearchViewData(), 'flight', $isPost, $csrfToken,
            [], 'Test', '1', '1', '1', '1', []
        );
    }
}

$t->test('Controller dispatches HTML actions with explicit input and rotates only the response token', function () use ($t): void {
    foreach (['flight' => new App\ViewModels\FlightSearchViewData(),
        'hotel' => new App\ViewModels\HotelSearchViewData(),
        'ferry' => new App\ViewModels\FerrySearchViewData()] as $type => $state) {
        $builder = new FakeSearchPageBuilder();
        $calls = [];
        $controller = new App\Controllers\SearchController([
            $type => static function ($input, $token) use (&$calls, $state) {
                $calls[] = [$input, $token];
                return $state;
            },
        ], [], $builder);
        $input = ['search_type' => $type, 'value' => 'unchanged'];
        $session = $_SESSION ?? null;
        $response = $controller->handle('POST', $input, 'original-token');
        $t->same([[$input, 'original-token']], $calls);
        $t->same($state, $builder->calls[0][0]);
        $t->same(true, $builder->calls[0][1]);
        $t->same(64, strlen($response->data->csrfToken));
        $t->true($response->data->csrfToken !== 'original-token', 'HTML token rotates');
        $t->same($session, $_SESSION ?? null, 'handle must not modify session');
    }
});

$t->test('Controller preserves default flight, unknown POST and GET dispatch contracts', function () use ($t): void {
    $builder = new FakeSearchPageBuilder();
    $state = new App\ViewModels\FlightSearchViewData();
    $count = 0;
    $controller = new App\Controllers\SearchController([
        'flight' => static function () use (&$count, $state) { $count++; return $state; },
    ], ['endpoint' => static function () { throw new RuntimeException('GET dispatched JSON'); }], $builder);
    $controller->handle('POST', [], 'token');
    $controller->handle('POST', ['search_type' => 'unknown'], 'token');
    $controller->handle('GET', ['search_type' => 'endpoint'], 'token');
    $t->same(1, $count);
    $t->same($state, $builder->calls[0][0]);
    $t->same([null, true], array_slice($builder->calls[1], 0, 2));
    $t->same([null, false], array_slice($builder->calls[2], 0, 2));
});

$t->test('Controller returns JSON unchanged without building HTML', function () use ($t): void {
    $builder = new FakeSearchPageBuilder();
    $expected = new App\Http\JsonResponse(['suggestions' => []], 422);
    $input = ['search_type' => 'endpoint', 'query' => 'test'];
    $controller = new App\Controllers\SearchController([
        'endpoint' => static function () { throw new RuntimeException('JSON dispatched HTML'); },
    ], [
        'endpoint' => static function ($actual, $token) use ($t, $input, $expected) {
            $t->same($input, $actual);
            $t->same('original-token', $token);
            return $expected;
        },
    ], $builder);
    $t->same($expected, $controller->handle('POST', $input, 'original-token'));
    $t->same([], $builder->calls);
});

// These assertions protect the intentional sharing and cache configuration policy.
function dependency(object $object, string $property): mixed
{
    return (new ReflectionProperty($object, $property))->getValue($object);
}

$t->test('Ferry factory shares route model and search service within a request', function () use ($t): void {
    $db = new PDO('sqlite::memory:');
    $action = App\Factories\FerrySearchFactory::create($db);
    $map = dependency($action, 'map');
    $t->same(dependency($action, 'routes'), dependency($map, 'routes'));
    $t->same(dependency($action, 'search'), dependency($map, 'presenter'));
    $other = App\Factories\FerrySearchFactory::create($db);
    $t->true(dependency($action, 'search') !== dependency($other, 'search'), 'No cross-request singleton');
});

$t->test('Composition root shares history across search actions and page assembly', function () use ($t): void {
    $controller = App\Core\SearchControllerFactory::create(new PDO('sqlite::memory:'), [], 'visitor');
    $actions = dependency($controller, 'searchActions');
    $flight = (new ReflectionFunction($actions['flight']))->getClosureThis();
    $hotel = (new ReflectionFunction($actions['hotel']))->getClosureThis();
    $history = dependency($flight, 'searchHistory');
    $t->same($history, dependency($hotel, 'searchHistory'));
    $t->same($history, dependency(dependency($controller, 'pageBuilder'), 'searchHistory'));
    $t->same(dependency($flight, 'flightCity'), dependency(dependency($flight, 'travelLinks'), 'cities'));
});

$t->test('Apify factory shares client and selects domain normalizers with separate cache policy', function () use ($t): void {
    $factory = new App\Factories\ApifySearchFactory([], '/fixture');
    $flight = $factory->flight();
    $hotel = $factory->hotel();
    $destination = $factory->destination();
    foreach ([$hotel, $destination] as $service) {
        $t->same(dependency($flight, 'client'), dependency($service, 'client'));
    }
    $t->same(App\Services\Normalizers\FlightResponseNormalizer::class, get_class(dependency($flight, 'normalizer')));
    $t->same(App\Services\Normalizers\HotelResponseNormalizer::class, get_class(dependency($hotel, 'normalizer')));
    $t->same(App\Services\Normalizers\DestinationResponseNormalizer::class, get_class(dependency($destination, 'normalizer')));
    foreach ([[$flight, 'flights', 3600], [$hotel, 'hotels', 3600], [$destination, 'place-suggestions', 900]] as [$service, $path, $ttl]) {
        $cache = dependency($service, 'cache');
        $t->same('/fixture/storage/cache/apify/' . $path, dependency($cache, 'directory'));
        $t->same($ttl, dependency($cache, 'ttl'));
    }
    $factory = new App\Factories\ApifySearchFactory([
        'flight_cache_dir' => '/custom/f', 'hotel_cache_dir' => '/custom/h', 'places_cache_dir' => '/custom/p',
        'cache_ttl' => -5, 'places_cache_ttl' => 42,
    ], '/fixture');
    foreach ([[$factory->flight(), '/custom/f', 0], [$factory->hotel(), '/custom/h', 0], [$factory->destination(), '/custom/p', 42]] as [$service, $path, $ttl]) {
        $cache = dependency($service, 'cache');
        $t->same($path, dependency($cache, 'directory'));
        $t->same($ttl, dependency($cache, 'ttl'));
    }
});
