<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_public_landing_renders_the_complete_confirmed_catalog_without_database_queries(): void
    {
        DB::shouldReceive('connection')->never();
        $response = $this->get('/')->assertOk();

        foreach (config('landing.services') as $service) {
            $response->assertSee($service['name'])->assertSee($service['description']);
        }

        $this->assertCount(8, config('landing.services'));
        $this->assertSame(8, substr_count($response->getContent(), 'class="landing-service-card"'));
        $this->assertSame(8, substr_count($response->getContent(), 'Precio provisional'));
        $response->assertSee('Los precios mostrados son provisionales');
    }

    public function test_public_navigation_and_confirmed_contact_links_use_real_destinations(): void
    {
        $response = $this->get('/')->assertOk();
        foreach (['inicio', 'quienes-somos', 'servicios', 'consejos-fiscales', 'contacto'] as $id) {
            $response->assertSee('id="'.$id.'"', false)->assertSee('href="#'.$id.'"', false);
        }

        $response->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('privacy.notice').'"', false)
            ->assertSee('https://wa.me/529992393507', false)
            ->assertSee('mailto:taxbalance2025@gmail.com', false)
            ->assertSee('https://www.instagram.com/taxbalance.wa/', false)
            ->assertSee('999 239 3507')->assertSee('@taxbalance.wa');
    }

    public function test_mission_vision_and_illustrative_content_are_identified_as_provisional(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('En W&A Balance somos un despacho dedicado a brindar soluciones contables, fiscales y jurídicas', false)
            ->assertSee('Nuestro propósito es ofrecer asesoría profesional y personalizada')
            ->assertSee('Creemos que una buena asesoría es fundamental')
            ->assertSee('Brindar servicios contables, fiscales y jurídicos de calidad')
            ->assertSee('Ser un despacho reconocido por su profesionalismo')
            ->assertSee('Textos institucionales propuestos, pendientes de aprobación')
            ->assertSee('Contenido provisional')->assertSee('Imagen ilustrativa provisional')
            ->assertSee('images/wa-balance-logo.svg', false);
    }

    public function test_unconfirmed_prices_are_not_published_and_the_catalog_is_not_limited_to_four_items(): void
    {
        $services = [];
        for ($i = 1; $i <= 9; $i++) {
            $services[] = ['name' => 'Servicio de prueba '.$i, 'description' => 'Descripción de prueba.', 'price' => '$123456', 'price_confirmed' => false];
        }
        config(['landing.services' => $services]);
        $response = $this->get('/')->assertOk()->assertDontSee('$123456');
        $this->assertSame(9, substr_count($response->getContent(), 'class="landing-service-card"'));
        $response->assertSee('Servicio de prueba 9');
    }

    public function test_missing_official_information_is_not_replaced_with_fictitious_contact_data(): void
    {
        config(['landing.services' => [], 'landing.contact' => []]);
        $this->get('/')->assertOk()->assertSee('El catálogo oficial de servicios está pendiente de confirmación.')
            ->assertDontSee('https://wa.me/', false)->assertDontSee('mailto:', false);
    }
}
