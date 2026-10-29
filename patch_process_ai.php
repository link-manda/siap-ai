<?php

use App\Models\Classification;

$aiDraftData = ['classification_id' => 1]; // mock
$classification = Classification::find($aiDraftData['classification_id']);
$role = auth()->user()->role ?? 'Admin';

if ($role === 'Admin' && $classification && $classification->level === 4) {
    $aiDraftData['extracted_text'] = '[REDACTED]';
    $aiDraftData['summary'] = '[REDACTED - Level 4]';
}
