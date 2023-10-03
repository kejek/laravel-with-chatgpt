<?php

namespace Tests\Feature;

use App\Http\Livewire\IrsOcr;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_route_is_registered(): void
    {
        $this->assertEquals('/', route('home', absolute: false));
        $this->assertEquals('/', action(IrsOcr::class, absolute: false));
    }

    public function test_expected_view_is_returned(): void
    {
        $this
            ->get(route('home'))
            ->assertSuccessful()
            ->assertSeeLivewire(IrsOcr::class);
    }
}
