<?php

use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireServiceProvider;

it('registers livewire and flux services in container', function () {
    expect(class_exists(LivewireServiceProvider::class))->toBeTrue();
    expect(class_exists(FluxServiceProvider::class))->toBeTrue();
});

it('renders blade directives for flux without error', function () {
    $renderedAppearance = Blade::render('@fluxAppearance');
    expect($renderedAppearance)->toContain('applyAppearance');

    $renderedScripts = Blade::render('@fluxScripts');
    expect($renderedScripts)->toContain('flux');
});
