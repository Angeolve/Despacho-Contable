<?php

return [
    // Populate only from the owner's official catalogue. No database is queried.
    // Each item: name, description, icon, price (optional), price_confirmed (bool).
    'services' => [
        ['name' => 'Asesoría fiscal y contable', 'description' => 'Orientación en materia fiscal y contable.', 'icon' => 'document', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Cálculo y presentación de impuestos', 'description' => 'Servicio de cálculo y presentación de impuestos.', 'icon' => 'calculator', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Trámites ante el SAT y AAFY', 'description' => 'Gestión de trámites ante el SAT y la AAFY.', 'icon' => 'folder', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Facturación electrónica', 'description' => 'Servicio de facturación electrónica.', 'icon' => 'document', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Cálculo de nómina', 'description' => 'Servicio de cálculo de nómina.', 'icon' => 'people', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Contabilidad general', 'description' => 'Servicio de contabilidad general.', 'icon' => 'chart', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Retroalimentación bimestral con análisis financiero', 'description' => 'Presentación bimestral de un análisis financiero.', 'icon' => 'chart', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
        ['name' => 'Calculadora preliminar de impuestos para docentes de secundaria', 'description' => 'Estimación preliminar de impuestos para docentes de secundaria.', 'icon' => 'calculator', 'price' => '$500', 'price_confirmed' => true, 'price_provisional' => true],
    ],

    'catalog_confirmed' => true,
    'institutional_copy_confirmed' => false,

    // Leave unavailable contact information unpublished.
    'contact' => [
        'phone' => '999 239 3507',
        'whatsapp' => '529992393507',
        'email' => 'taxbalance2025@gmail.com',
        'address' => null,
        'social_links' => [
            ['label' => 'Instagram', 'handle' => '@taxbalance.wa', 'url' => 'https://www.instagram.com/taxbalance.wa/'],
        ],
    ],
];
