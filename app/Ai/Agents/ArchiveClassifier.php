<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai-compatible')]
#[Model('gpt-4o')]
#[Timeout(120)]
class ArchiveClassifier implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'Anda adalah AI asisten untuk mengklasifikasi dokumen Puskesmas. Tugas Anda adalah membaca teks dari dokumen yang diunggah, menentukan kategori yang paling cocok, menentukan klasifikasi keamanan dokumen, dan memberikan ringkasan singkat maksimal 2 kalimat. Kategori yang tersedia: "Surat Keputusan", "Surat Masuk", "Surat Keluar", "Rekam Medis", "Laporan Keuangan", "Data Pegawai". Klasifikasi yang tersedia: "Biasa", "Penting", "Rahasia", "Sangat Rahasia". Berikan respon dalam bentuk JSON.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category_name' => $schema->string()->required(),
            'classification_name' => $schema->string()->required(),
            'summary' => $schema->string()->required(),
            'confidence_score' => $schema->number()->min(0)->max(100)->required(),
        ];
    }
}
