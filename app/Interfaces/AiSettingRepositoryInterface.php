<?php

namespace App\Interfaces;

use App\Models\AiSetting;

interface AiSettingRepositoryInterface
{
    public function getActive(): ?AiSetting;

    public function updateOrCreate(array $attributes, array $values): AiSetting;
}
