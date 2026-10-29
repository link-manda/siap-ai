@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header (Selaras dengan Manajemen Arsip & font standar SIAP-AI) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Pengaturan Provider AI</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Kelola penyedia model kecerdasan buatan, URL endpoint, API key, dan batas waktu untuk klasifikasi dokumen otomatis.
            </p>
        </div>
        <div>
            <flux:badge color="zinc" icon="shield-check">Khusus Pimpinan</flux:badge>
        </div>
    </div>

    {{-- Validation Errors Banner --}}
    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle">
            <p class="font-semibold text-xs mb-1">Terdapat kesalahan pengisian formulir:</p>
            <ul class="list-disc pl-4 space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    {{-- Alpine Container (Sanitized Data Injection: SEC-01) --}}
    <div 
        x-data="aiSettingsManager()"
        class="space-y-6"
    >
        <form id="ai-settings-form" action="{{ route('settings.ai.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {{-- Panel Kiri: Kredensial & Endpoint (7 Kolom) --}}
                <div class="lg:col-span-7 space-y-6">
                    <flux:card class="space-y-5">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">1. Kredensial & Endpoint Provider</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Pilih penyedia layanan AI dan tentukan alamat endpoint serta kunci API.</p>
                        </div>

                        {{-- Provider Selection (UI-05: Flux Select Option) --}}
                        <div>
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Penyedia AI (Provider) <span class="text-rose-500">*</span></flux:label>
                                <flux:select name="provider" x-model="provider" x-on:change="onProviderChange()">
                                    <flux:select.option value="openai-compatible">OpenAI-Compatible / Custom Gateway / Ollama Gateway</flux:select.option>
                                    <flux:select.option value="gemini">Google Gemini</flux:select.option>
                                    <flux:select.option value="openai">OpenAI Official</flux:select.option>
                                    <flux:select.option value="ollama">Ollama Server (Local Private Model)</flux:select.option>
                                    <flux:select.option value="deepseek">DeepSeek AI</flux:select.option>
                                    <flux:select.option value="anthropic">Anthropic Claude</flux:select.option>
                                </flux:select>
                                <flux:description class="text-xs text-zinc-500 dark:text-zinc-400">Pilih penyedia layanan model AI yang ingin digunakan untuk proses klasifikasi dokumen arsip.</flux:description>
                                <flux:error name="provider" />
                            </flux:field>
                        </div>

                        {{-- Base URL --}}
                        <div>
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    Base URL / Endpoint
                                    <template x-if="isUrlRequired()">
                                        <span class="text-rose-500">*</span>
                                    </template>
                                </flux:label>
                                <flux:input 
                                    name="base_url" 
                                    type="text" 
                                    x-model="baseUrl" 
                                    placeholder="https://api.openai.com/v1 atau http://localhost:11434" 
                                />
                                <flux:description class="text-xs text-zinc-500 dark:text-zinc-400">
                                    <span x-text="getUrlDescription()"></span>
                                </flux:description>
                                <flux:error name="base_url" />
                            </flux:field>
                        </div>

                        {{-- API Key (UI-01: Native viewable, UI-10: State sync, UI-08/09: Mobile flex-wrap) --}}
                        <div class="space-y-2">
                            <flux:field>
                                <div class="flex flex-wrap items-center justify-between gap-1.5 mb-1">
                                    <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Kunci API (API Key)</flux:label>
                                    <template x-if="hasSavedKey && provider === initialProvider">
                                        <flux:badge color="emerald" size="sm" icon="check-circle">Kunci Tersimpan di Basis Data</flux:badge>
                                    </template>
                                </div>
                                <flux:input 
                                    name="api_key" 
                                    type="password" 
                                    viewable
                                    x-model="apiKey" 
                                    x-bind:placeholder="(hasSavedKey && provider === initialProvider) ? '•••••••••••• (Kosongkan jika tidak ingin mengubah kunci tersimpan)' : 'Masukkan API Key baru untuk penyedia ini'" 
                                />
                                <flux:description class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Kunci disimpan dengan enkripsi native Laravel (AES-256) dan tidak akan dibocorkan ke log audit.
                                </flux:description>
                                <flux:error name="api_key" />
                            </flux:field>
                        </div>
                    </flux:card>
                </div>

                {{-- Panel Kanan: Parameter Model & Uji Koneksi (5 Kolom) --}}
                <div class="lg:col-span-5 space-y-6">
                    <flux:card class="space-y-5">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">2. Parameter Model & Uji Koneksi</h2>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Tentukan identifier model dan uji latensi respons sistem.</p>
                        </div>

                        {{-- Model Name & Presets (UI-02: Keyboard accessibility) --}}
                        <div class="space-y-3">
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Nama Model AI <span class="text-rose-500">*</span></flux:label>
                                <flux:input 
                                    name="model" 
                                    type="text" 
                                    x-model="model" 
                                    placeholder="gpt-4o, gemini-2.5-flash, llama3.2" 
                                />
                                <flux:description class="text-xs text-zinc-500 dark:text-zinc-400">Nama identifier model yang akan dipanggil saat proses klasifikasi.</flux:description>
                                <flux:error name="model" />
                            </flux:field>

                            {{-- Dynamic Preset Badges --}}
                            <div>
                                <span class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1.5">
                                    Rekomendasi model (klik untuk memilih):
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="preset in getModelPresets()" :key="preset">
                                        <button 
                                            type="button" 
                                            x-on:click="model = preset"
                                            x-bind:class="model === preset 
                                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-600 font-semibold' 
                                                : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-normal'"
                                            class="text-xs px-2.5 py-1 rounded-md border transition focus:outline-hidden focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-pointer"
                                        >
                                            <span x-text="preset"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Timeout --}}
                        <div>
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Batas Waktu Tunggu / Timeout (Detik) <span class="text-rose-500">*</span></flux:label>
                                <flux:input 
                                    name="timeout" 
                                    type="number" 
                                    x-model="timeout" 
                                    min="5" 
                                    max="300" 
                                />
                                <flux:description class="text-xs text-zinc-500 dark:text-zinc-400">Durasi maksimal menunggu respons AI saat menganalisis dokumen (default: 60 detik).</flux:description>
                                <flux:error name="timeout" />
                            </flux:field>
                        </div>

                        <flux:separator />

                        {{-- Interactive Test Connection Area (UI-03: Symmetric Callout, UI-11: Spin animation) --}}
                        <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-900/60 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <span class="text-xs font-semibold text-zinc-900 dark:text-white block">Uji Koneksi Provider</span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 block mt-0.5">
                                        Uji apakah endpoint dan model yang Anda tentukan dapat merespons sebelum menyimpan.
                                    </span>
                                </div>
                                <flux:button 
                                    type="button" 
                                    variant="subtle" 
                                    size="sm"
                                    icon="arrow-path" 
                                    x-on:click="testConnection()"
                                    x-bind:disabled="isTesting"
                                    x-bind:class="isTesting ? 'opacity-75 pointer-events-none' : ''"
                                >
                                    <span x-text="isTesting ? 'Menguji...' : 'Uji Koneksi'"></span>
                                </flux:button>
                            </div>

                            {{-- Real-time Test Feedback --}}
                            <div x-show="testStatus === 'success'" x-cloak class="mt-2">
                                <flux:callout variant="success" icon="check-circle">
                                    <span class="text-xs" x-text="testMessage"></span>
                                </flux:callout>
                            </div>

                            <div x-show="testStatus === 'error'" x-cloak class="mt-2">
                                <flux:callout variant="danger" icon="exclamation-circle">
                                    <span class="text-xs" x-text="testMessage"></span>
                                </flux:callout>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="pt-2">
                            <flux:modal.trigger name="confirm-save-ai">
                                <flux:button 
                                    type="button" 
                                    variant="primary" 
                                    class="w-full justify-center"
                                    icon="check" 
                                    x-on:click="openConfirmModal()"
                                >
                                    Simpan Konfigurasi
                                </flux:button>
                            </flux:modal.trigger>
                        </div>
                    </flux:card>
                </div>
            </div>
        </form>

        {{-- Flux Modal Confirmation (UI-07: Border contrast, UI-12: Submit protection) --}}
        <flux:modal name="confirm-save-ai" class="md:w-96 text-left">
            <div class="space-y-4">
                <div class="space-y-2">
                    <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Konfirmasi Pengaturan AI</flux:heading>
                    <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 whitespace-normal">
                        Apakah Anda yakin ingin memperbarui konfigurasi penyedia AI? Konfigurasi ini akan langsung aktif digunakan oleh seluruh sistem SIAP-AI.
                    </flux:subheading>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button variant="subtle" x-bind:disabled="isSubmitting">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button 
                        type="button" 
                        variant="primary" 
                        icon="check" 
                        x-on:click="submitForm()"
                        x-bind:disabled="isSubmitting"
                    >
                        <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan & Terapkan'"></span>
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </div>
</div>

@push('scripts')
<script>
    const aiInitialSetting = @json($setting);
    const aiHasSavedKey = @json($has_saved_key);
    const aiTestUrl = @json(route('settings.ai.test'));
    const aiCsrfToken = @json(csrf_token());

    function aiSettingsManager() {
        return {
            initialProvider: aiInitialSetting?.provider || 'openai-compatible',
            provider: aiInitialSetting?.provider || 'openai-compatible',
            baseUrl: aiInitialSetting?.base_url || '',
            apiKey: '',
            model: aiInitialSetting?.model || 'gpt-41',
            timeout: aiInitialSetting?.timeout || 60,
            hasSavedKey: Boolean(aiHasSavedKey),
            isTesting: false,
            isSubmitting: false,
            testStatus: null, // null | 'success' | 'error'
            testMessage: '',

            presets: {
                'openai-compatible': {
                    defaultUrl: 'https://api.openai.com/v1',
                    models: ['gpt-4o', 'gpt-4o-mini', 'gpt-41', 'meta-llama/llama-3.1-70b-instruct']
                },
                'gemini': {
                    defaultUrl: 'https://generativelanguage.googleapis.com/v1beta/',
                    models: ['gemini-2.5-flash', 'gemini-2.5-pro', 'gemini-1.5-flash']
                },
                'openai': {
                    defaultUrl: 'https://api.openai.com/v1',
                    models: ['gpt-4o', 'gpt-4o-mini', 'gpt-41']
                },
                'ollama': {
                    defaultUrl: 'http://localhost:11434',
                    models: ['llama3.2', 'deepseek-r1', 'qwen2.5', 'mistral']
                },
                'deepseek': {
                    defaultUrl: 'https://api.deepseek.com/v1',
                    models: ['deepseek-chat', 'deepseek-reasoner']
                },
                'anthropic': {
                    defaultUrl: 'https://api.anthropic.com/v1',
                    models: ['claude-3-5-sonnet-latest', 'claude-3-5-haiku-latest']
                }
            },

            getModelPresets() {
                return this.presets[this.provider]?.models || [];
            },

            isUrlRequired() {
                return this.provider === 'openai-compatible' || this.provider === 'ollama';
            },

            getUrlDescription() {
                if (this.provider === 'ollama') {
                    return 'Wajib diisi dengan URL server Ollama lokal, misal: http://192.168.1.100:11434 atau http://localhost:11434';
                }
                if (this.provider === 'openai-compatible') {
                    return 'Wajib diisi dengan endpoint gateway atau custom proxy OpenAI-Compatible.';
                }
                return 'Opsional jika menggunakan API Cloud resmi. Kosongkan untuk menggunakan URL default penyedia.';
            },

            onProviderChange() {
                const config = this.presets[this.provider];
                if (config) {
                    if (!this.baseUrl || this.baseUrl.startsWith('http')) {
                        this.baseUrl = config.defaultUrl;
                    }
                    if (config.models.length > 0 && !config.models.includes(this.model)) {
                        this.model = config.models[0];
                    }
                }
                this.testStatus = null;
                this.testMessage = '';
            },

            async testConnection() {
                this.isTesting = true;
                this.testStatus = null;
                this.testMessage = '';

                try {
                    const response = await fetch(aiTestUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': aiCsrfToken
                        },
                        body: JSON.stringify({
                            provider: this.provider,
                            base_url: this.baseUrl || null,
                            api_key: this.apiKey || null,
                            model: this.model,
                            timeout: Math.min(15, Math.max(3, parseInt(this.timeout) || 10))
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.testStatus = 'success';
                        this.testMessage = data.message || 'Koneksi berhasil terhubung ke penyedia AI.';
                    } else {
                        this.testStatus = 'error';
                        this.testMessage = data.message || 'Gagal terhubung ke penyedia AI.';
                    }
                } catch (err) {
                    this.testStatus = 'error';
                    this.testMessage = 'Gagal menghubungi server aplikasi untuk melakukan tes koneksi: ' + err.message;
                } finally {
                    this.isTesting = false;
                }
            },

            openConfirmModal() {
                this.$dispatch('modal-show', { name: 'confirm-save-ai' });
            },

            submitForm() {
                this.isSubmitting = true;
                const form = document.getElementById('ai-settings-form');
                if (form) {
                    form.submit();
                }
            }
        };
    }

    window.aiSettingsManager = aiSettingsManager;
    document.addEventListener('alpine:init', () => {
        if (window.Alpine) {
            window.Alpine.data('aiSettingsManager', aiSettingsManager);
        }
    });
</script>
@endpush
@endsection
