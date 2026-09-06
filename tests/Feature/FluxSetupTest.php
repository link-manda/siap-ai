<?php

it('registers livewire and flux services in container', function () {
    expect(class_exists(\Livewire\LivewireServiceProvider::class))->toBeTrue();
    expect(class_exists(\Flux\FluxServiceProvider::class))->toBeTrue();
});

it('renders blade directives for flux without error', function () {
    $renderedAppearance = \Illuminate\Support\Facades\Blade::render('@fluxAppearance');
    expect($renderedAppearance)->toContain('applyAppearance');

    $renderedScripts = \Illuminate\Support\Facades\Blade::render('@fluxScripts');
    expect($renderedScripts)->toContain('flux');
});
