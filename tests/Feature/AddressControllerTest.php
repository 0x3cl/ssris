<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    public function test_regions_are_fetched_from_the_psgc_api(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/regions/' => Http::response([
                ['code' => '040000000', 'name' => 'CALABARZON'],
                ['code' => '130000000', 'name' => 'NCR'],
            ]),
        ]);

        $response = $this->getJson('/address/regions');

        $response->assertOk();
        $response->assertJson(['regions' => [
            ['code' => '040000000', 'name' => 'CALABARZON'],
            ['code' => '130000000', 'name' => 'NCR'],
        ]]);
    }

    public function test_provinces_are_fetched_for_a_region(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/regions/040000000/provinces/' => Http::response([
                ['code' => '043400000', 'name' => 'Laguna'],
            ]),
        ]);

        $response = $this->getJson('/address/regions/040000000/provinces');

        $response->assertOk();
        $response->assertJson(['provinces' => [['code' => '043400000', 'name' => 'Laguna']], 'municipalities' => []]);
    }

    public function test_region_without_provinces_returns_its_municipalities_directly(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/regions/130000000/provinces/' => Http::response([]),
            'psgc.gitlab.io/api/regions/130000000/cities-municipalities/' => Http::response([
                ['code' => '133900000', 'name' => 'City of Manila'],
            ]),
        ]);

        $response = $this->getJson('/address/regions/130000000/provinces');

        $response->assertOk();
        $response->assertJson(['provinces' => [], 'municipalities' => [['code' => '133900000', 'name' => 'Manila City']]]);
    }

    public function test_municipalities_are_fetched_for_a_province_and_city_names_are_normalized(): void
    {
        Http::fake([
            'psgc.gitlab.io/api/provinces/043400000/cities-municipalities/' => Http::response([
                ['code' => '043402000', 'name' => 'Bay'],
                ['code' => '043403000', 'name' => 'City of Biñan'],
            ]),
        ]);

        $response = $this->getJson('/address/provinces/043400000/municipalities');

        $response->assertOk();
        $response->assertJson(['municipalities' => [
            ['code' => '043402000', 'name' => 'Bay'],
            ['code' => '043403000', 'name' => 'Biñan City'],
        ]]);
    }
}
