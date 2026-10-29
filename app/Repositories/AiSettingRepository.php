<?php

namespace App\Repositories;

use App\Interfaces\AiSettingRepositoryInterface;
use App\Models\AiSetting;

class AiSettingRepository implements AiSettingRepositoryInterface
{
    public function getActive(): ?AiSetting
    {
        return AiSetting::where('is_active', true)->first();
    }

    public function updateOrCreate(array $attributes, array $values): AiSetting
    {
        return AiSetting::updateOrCreate($attributes, $values);
    }
}
