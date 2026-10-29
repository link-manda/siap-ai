# Audit & Spesifikasi Migrasi Dialog Konfirmasi Bawaan Peramban ke `<flux:modal>` (SIAP-AI)

Dokumen ini merupakan laporan audit komprehensif, independen, dan berstandar teknis tinggi mengenai seluruh penggunaan mekanisme dialog konfirmasi bawaan peramban (*browser native confirm dialog*) pada antarmuka aplikasi **SIAP-AI** (Sistem Kearsipan Digital Berbasis AI). Laporan ini merinci inventarisasi temuan, analisis kelemahan teknis, serta cetak biru arsitektur (*architectural blueprints*) untuk memigrasikan seluruh dialog tersebut ke komponen resmi **`<flux:modal>`** dari pustaka **Flux UI**.

---

## 1. Executive Summary

### 1.1 Latar Belakang & Konteks Sistem
Aplikasi SIAP-AI dibangun di atas stack modern **Laravel 12**, **Livewire 3**, **Flux UI (Free Edition)**, dan **Tailwind CSS**. Sesuai dengan panduan arsitektur dan standar kualitas sistem (`AGENTS.md` - Standar Arsitektur SIAP-AI §3), seluruh elemen antarmuka pengguna diwajibkan menggunakan komponen resmi Flux UI (`<flux:modal>`, `<flux:button>`, `<flux:icon>`) demi menjaga:
1. Konsistensi visual dan proporsi elemen antarmuka.
2. Kompatibilitas penuh dengan tema ganda (*light mode* dan *dark mode*) berbasis palet warna Zinc.
3. Aksesibilitas bawaan (*built-in accessibility/a11y*), termasuk perangkap fokus (*focus trap*), navigasi keyboard (*Escape key to dismiss*), dan atribut ARIA.
4. Kepatuhan mutlak terhadap aturan **Zero Emoji Policy** (larangan penggunaan emoji sebagai ikon UI).

### 1.2 Cakupan Audit (Scope of Audit)
Audit statis yang mendalam dan *read-only* telah dilakukan terhadap seluruh aset antarmuka sistem:
- **20 berkas template Blade** di bawah direktori `resources/views/` (mencakup `pages/`, `layouts/`, `components/`, `livewire/`, `flux/`, dan `partials/`).
- Seluruh bundel skrip JavaScript di direktori `resources/js/` (`app.js`, `bootstrap.js`) dan folder kompilasi `public/`.
- Seluruh komponen reaktif backend di direktori `app/Livewire/` (`ArchiveTable.php`, `AuditTrailTable.php`, `UserTable.php`).

### 1.3 Ringkasan Hasil Temuan
Dari pemindaian menyeluruh tersebut, ditemukan tepat **3 kemunculan** dialog konfirmasi bawaan peramban (*native confirm dialog*) pada **2 berkas template Blade**:
1. `resources/views/pages/archives/trash.blade.php` (Baris 55): `onclick="return confirm('Kembalikan dokumen ini ke daftar arsip aktif?')"` — Pemulihan arsip dari tong sampah (*restore*).
2. `resources/views/pages/archives/trash.blade.php` (Baris 66): `onclick="return confirm('PERINGATAN: Tindakan ini akan menghapus arsip secara permanen dari sistem dan tidak dapat dibatalkan. Lanjutkan?')"` — Penghapusan permanen arsip (*force destroy / hard delete*).
3. `resources/views/livewire/user-table.blade.php` (Baris 83): `wire:confirm="Yakin ingin menghapus akun pengguna '{{ $user->name }}'?"` — Penghapusan akun pengguna oleh Administrator via Livewire.

Sementara itu, pada `resources/js/` ditemukan **0 kemunculan** `confirm()` atau `window.confirm()`. Pemanggilan `confirm` lain pada views hanyalah atribut nama input form otentikasi (`password_confirmation` pada `create.blade.php` dan `edit.blade.php`) yang bukan merupakan dialog interaktif.

### 1.4 Benchmark Standar Emas (*Golden Standard Reference*)
Sistem SIAP-AI telah memiliki referensi implementasi modal Flux UI yang teruji dan berjalan sempurna pada komponen `resources/views/livewire/archive-table.blade.php` (baris 190–214) yang dikendalikan oleh `app/Livewire/ArchiveTable.php` (baris 104–145). Implementasi ini menggunakan modal terprogram `\Flux::modal('delete-reason-modal')->show()` yang dilengkapi input alasan penghapusan, tombol bahaya (*danger variant*), penanganan state pemuatan (*wire:loading*), dan notifikasi reaktif *toast*. Pola ini menjadi acuan utama standardisasi seluruh dialog konfirmasi di SIAP-AI.

---

## 2. Comprehensive Findings Inventory Table

Tabel berikut menyajikan inventarisasi lengkap ketiga temuan dialog konfirmasi bawaan peramban yang memerlukan migrasi ke `<flux:modal>`:

| ID | Berkas (Path Relatif) | Berkas (Path Absolut) | Baris (1-indexed) | Mekanisme Pemicu Eksisting | Konteks Fitur / Aksi | Tingkat Risiko / Dampak |
|---|---|---|---|---|---|---|
| **CONF-01** | `resources/views/pages/archives/trash.blade.php` | `/Users/brahmanda/Sites/siap-ai/resources/views/pages/archives/trash.blade.php` | 55 | `onclick="return confirm(...)"` | Pemulihan dokumen dari Tong Sampah ke arsip aktif (`POST /archives/{id}/restore`) | Rendah (*State restoration*) |
| **CONF-02** | `resources/views/pages/archives/trash.blade.php` | `/Users/brahmanda/Sites/siap-ai/resources/views/pages/archives/trash.blade.php` | 66 | `onclick="return confirm(...)"` | Penghapusan arsip fisik & data secara permanen (`DELETE /archives/{id}/force-destroy`) | **KRITIS** (*Irreversible physical & DB deletion*) |
| **CONF-03** | `resources/views/livewire/user-table.blade.php` | `/Users/brahmanda/Sites/siap-ai/resources/views/livewire/user-table.blade.php` | 83 | `wire:confirm="..."` | Penghapusan akun pengguna dari sistem Puskesmas (`UserTable::deleteUser`) | Tinggi (*Account revocation & security*) |

---

## 3. Detailed Audit Findings & Technical Analysis

---

### 3.1 Temuan CONF-01: Pemulihan Arsip dari Tong Sampah

#### A. Lokasi & Pemicu
- **Path Relatif**: `resources/views/pages/archives/trash.blade.php`
- **Path Absolut**: `/Users/brahmanda/Sites/siap-ai/resources/views/pages/archives/trash.blade.php`
- **Nomor Baris**: 55 (1-indexed)
- **Mekanisme Pemicu**: Atribut JavaScript inline `onclick="return confirm('...')"`.

#### B. Cuplikan Kode Sumber Verbatim (Baris 51–60)
```blade
<form action="{{ route('archives.restore', $archive->id) }}" method="POST" class="inline-block">
    @csrf
    <button 
        type="submit" 
        onclick="return confirm('Kembalikan dokumen ini ke daftar arsip aktif?')"
        title="Restore" 
        class="p-1.5 text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
        <flux:icon icon="arrow-uturn-left" class="size-4" />
    </button>
</form>
```

#### C. Konteks Fitur & Alur Kerja Bisnis
- **Modul**: Manajemen Tong Sampah Arsip (*Archive Trash Management*).
- **Aktor Berwenang**: Pengguna dengan wewenang `Pimpinan` (diatur melalui `ArchivePolicy@restore` dan `RoleMiddleware`).
- **Target Endpoint**: Route `archives.restore` (`POST /archives/{id}/restore`), dieksekusi oleh metode `ArchiveController@restore`.
- **Dampak Operasi**: Memulihkan arsip berstatus *soft-deleted* (`deleted_at = null`), menghapus catatan `delete_reason`, dan mencatat riwayat audit trail (*AuditTrail::log*).

#### D. Analisis Keterbatasan Teknis Native Confirm
1. **Thread Blocking**: Pemanggilan `window.confirm()` menghentikan (*freeze*) seluruh thread JavaScript peramban, menghentikan transisi visual dan rendering halaman sementara waktu.
2. **Ketidakcocokan Tema (Dark Mode Mismatch)**: Dialog bawaan sistem operasi/peramban selalu muncul dengan jendela modal putih terang standar OS. Hal ini sangat mengganggu saat pengguna mengaktifkan mode gelap (*dark mode*) pada antarmuka SIAP-AI.
3. **Ketiadaan Konteks Metadata**: Dialog hanya berupa sebaris string teks mentah tanpa rincian judul dokumen, nomor berkas, atau informasi siapa yang menghapusnya sebelumnya.
4. **Respon Sentuh Seluler Buruk**: Pada peramban perangkat seluler/tablet, dialog sistem muncul terpisah dari posisi tombol dan rawan tertutup keyboard virtual atau salah tekan.

#### E. Rekomendasi Desain Pengganti `<flux:modal>`

Pada berkas `trash.blade.php` (tabel dengan paginasi 10 rekaman), pola terstandardisasi menggunakan pasangan `<flux:modal.trigger>` dan `<flux:modal>` per baris adalah pendekatan yang paling deklaratif, bebas *state mismatch*, dan langsung kompatibel dengan form server-rendered Blade:

```blade
{{-- Trigger Button --}}
<flux:modal.trigger name="restore-archive-{{ $archive->id }}">
    <flux:button 
        variant="subtle" 
        size="sm" 
        square 
        title="Pulihkan Arsip" 
        class="text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <flux:icon icon="arrow-uturn-left" class="size-4" />
    </flux:button>
</flux:modal.trigger>

{{-- Modal Konfirmasi Pemulihan --}}
<flux:modal name="restore-archive-{{ $archive->id }}" class="md:w-96">
    <form action="{{ route('archives.restore', $archive->id) }}" method="POST" class="space-y-4">
        @csrf
        <div class="space-y-2">
            <div class="flex items-center gap-2.5 text-emerald-600 dark:text-emerald-400">
                <div class="size-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 flex items-center justify-center shrink-0">
                    <flux:icon icon="arrow-uturn-left" class="size-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Pulihkan Arsip</flux:heading>
            </div>
            
            <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                Apakah Anda yakin ingin mengembalikan dokumen <strong class="text-zinc-900 dark:text-white font-semibold">"{{ $archive->title }}"</strong> ke daftar arsip aktif?
            </flux:subheading>

            <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-800 text-[11px] text-zinc-500 space-y-1">
                <div>Nomor Arsip: <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $archive->document_number ?? '-' }}</span></div>
                <div>Alasan Dihapus: <span class="text-rose-500 italic">"{{ $archive->delete_reason ?? '-' }}"</span></div>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <flux:modal.close>
                <flux:button variant="subtle">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-500 text-white">
                Pulihkan Arsip
            </flux:button>
        </div>
    </form>
</flux:modal>
```

---

### 3.2 Temuan CONF-02: Hapus Permanen Arsip (*Force Destroy*)

#### A. Lokasi & Pemicu
- **Path Relatif**: `resources/views/pages/archives/trash.blade.php`
- **Path Absolut**: `/Users/brahmanda/Sites/siap-ai/resources/views/pages/archives/trash.blade.php`
- **Nomor Baris**: 66 (1-indexed)
- **Mekanisme Pemicu**: Atribut JavaScript inline `onclick="return confirm('...')"`.

#### B. Cuplikan Kode Sumber Verbatim (Baris 61–72)
```blade
<form action="{{ route('archives.force-destroy', $archive->id) }}" method="POST" class="inline-block">
    @csrf
    @method('DELETE')
    <button 
        type="submit" 
        onclick="return confirm('PERINGATAN: Tindakan ini akan menghapus arsip secara permanen dari sistem dan tidak dapat dibatalkan. Lanjutkan?')"
        title="Hapus Permanen" 
        class="p-1.5 text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
        <flux:icon icon="fire" class="size-4" />
    </button>
</form>
```

#### C. Konteks Fitur & Alur Kerja Bisnis
- **Modul**: Manajemen Tong Sampah Arsip (*Archive Trash Management*).
- **Aktor Berwenang**: Eksklusif role `Pimpinan` (`ArchivePolicy@forceDelete`).
- **Target Endpoint**: Route `archives.force-destroy` (`DELETE /archives/{id}/force`), dieksekusi oleh metode `ArchiveController@forceDestroy`.
- **Dampak Operasi**: Menghapus rekaman arsip secara permanen dari basis data (`$archive->forceDelete()`), menghapus fisik berkas PDF/dokumen asli dari disk penyimpanan server (`Storage::disk('public')->delete(...)`), serta mencatat insiden penghapusan ke tabel jejak audit (`AuditTrail`).

#### D. Analisis Keterbatasan Teknis & Risiko Keamanan
1. **Risiko Fatal Ketidaksengajaan (*Accidental Destruction*)**: Karena dialog peramban native memfokuskan tombol `OK` secara default pada beberapa platform peramban, penekanan tombol `Enter` atau `Spasi` yang tidak disengaja oleh pengguna akan langsung mengeksekusi penghapusan permanen tanpa kesempatan pemulihan data.
2. **Ketiadaan Aksen Bahaya Visual (*Danger Accents*)**: Dialog bawaan peramban tidak dapat menampilkan warna peringatan merah (*danger badge*), ikon peringatan bahaya, atau teks penjelas konsekuensi hilangnya file fisik.
3. **Ketidaksesuaian Standar Desain**: Tombol antarmuka telah menggunakan ikon resmi `<flux:icon icon="fire" />`, namun ketika diklik justru memicu jendela dialog tahun 1995 tanpa keterpaduan visual dengan Flux UI.

#### E. Rekomendasi Desain Pengganti `<flux:modal>`

Mengganti pemicu dengan `<flux:modal.trigger>` dan menyediakan dialog konfirmasi bahaya tinggi dengan penegasan nama dokumen dan batasan aksi:

```blade
{{-- Trigger Button --}}
<flux:modal.trigger name="force-delete-archive-{{ $archive->id }}">
    <flux:button 
        variant="subtle" 
        size="sm" 
        square 
        title="Hapus Permanen" 
        class="text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <flux:icon icon="fire" class="size-4" />
    </flux:button>
</flux:modal.trigger>

{{-- Modal Konfirmasi Hapus Permanen --}}
<flux:modal name="force-delete-archive-{{ $archive->id }}" class="md:w-96">
    <form action="{{ route('archives.force-destroy', $archive->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('DELETE')
        <div class="space-y-3">
            <div class="flex items-center gap-2.5 text-rose-600 dark:text-rose-400">
                <div class="size-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 flex items-center justify-center shrink-0">
                    <flux:icon icon="exclamation-triangle" class="size-4 text-rose-600 dark:text-rose-400" />
                </div>
                <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Hapus Arsip Permanen</flux:heading>
            </div>
            
            <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                Tindakan ini akan menghapus arsip <strong class="text-zinc-900 dark:text-white font-semibold">"{{ $archive->title }}"</strong> beserta seluruh berkas fisik dari server.
            </flux:subheading>

            <div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-xs text-rose-700 dark:text-rose-300">
                <span class="font-bold">PERINGATAN:</span> Tindakan ini bersifat permanen dan data <span class="underline font-semibold">tidak dapat dipulihkan</span> kembali oleh sistem.
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
            <flux:modal.close>
                <flux:button variant="subtle">Batal</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="danger">
                Hapus Selamanya
            </flux:button>
        </div>
    </form>
</flux:modal>
```

---

### 3.3 Temuan CONF-03: Hapus Akun Pengguna pada Livewire User Table

#### A. Lokasi & Pemicu
- **Path Relatif**: `resources/views/livewire/user-table.blade.php`
- **Path Absolut**: `/Users/brahmanda/Sites/siap-ai/resources/views/livewire/user-table.blade.php`
- **Nomor Baris**: 83 (1-indexed)
- **Mekanisme Pemicu**: Direktif bawaan Livewire 3 `wire:confirm="..."` yang secara internal membungkus pemanggilan `window.confirm()`.

#### B. Cuplikan Kode Sumber Verbatim (Baris 79–88)
```blade
@if (auth()->id() !== $user->id)
    <button 
        type="button" 
        wire:click="deleteUser({{ $user->id }})" 
        wire:confirm="Yakin ingin menghapus akun pengguna '{{ $user->name }}'?" 
        title="Hapus Pengguna" 
        class="p-1.5 text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
        <flux:icon icon="trash" class="size-4" />
    </button>
@endif
```

#### C. Konteks Fitur & Alur Kerja Bisnis
- **Modul**: Manajemen Pengguna & Hak Akses RBAC (`App\Livewire\UserTable`).
- **Aktor Berwenang**: Administrator / Super Admin (`UserPolicy@delete`).
- **Target Endpoint**: Action Livewire `deleteUser(int $id)` pada komponen `App\Livewire\UserTable`.
- **Dampak Operasi**: Menghapus akun pengguna dari database, mencabut akses login, dan memicu notifikasi toast `Pengguna berhasil dihapus.`.

#### D. Analisis Keterbatasan Teknis `wire:confirm`
1. **Syntactic Sugar atas `window.confirm()`**: Meskipun ditulis secara elegan via direktif Livewire `wire:confirm`, secara esensial Livewire hanya menginjeksikan panggilan `window.confirm(message)` pada layer JavaScript Alpine/Livewire.
2. **Inkonsistensi dengan `ArchiveTable`**: Komponen `ArchiveTable` pada halaman arsip menggunakan modal modern `<flux:modal name="delete-reason-modal">`, sementara `UserTable` masih menggunakan popup peramban mentah. Hal ini memunculkan disonansi pengalaman pengguna (*UX discrepancy*) antar halaman di dalam sistem yang sama.
3. **Keterbatasan Pelaporan Error**: Jika otorisasi gagal atau user menghapus diri sendiri, popup native telah terlanjur ditutup tanpa status transisi yang jelas.

#### E. Rekomendasi Desain Pengganti `<flux:modal>`

Mengikuti standar emas dari `ArchiveTable.php`, komponen `UserTable` diperkaya dengan state modal konfirmasi dan metode `confirmDeleteUser($id)` serta `executeDeleteUser()`.

##### 1. Pembaruan Komponen PHP Backend (`app/Livewire/UserTable.php`)
```php
<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public int $perPage = 10;

    // State untuk pelacakan modal konfirmasi Flux
    public ?int $userToDeleteId = null;
    public ?string $userToDeleteName = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Membuka modal konfirmasi Flux dan mencatat user sasaran
     */
    public function confirmDeleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            $this->dispatch('toast-show', 
                slots: ['text' => 'Anda tidak dapat menghapus akun Anda sendiri.', 'heading' => 'Kesalahan'], 
                dataset: ['variant' => 'danger'], 
                duration: 0
            );
            return;
        }

        $this->userToDeleteId = $user->id;
        $this->userToDeleteName = $user->name;

        \Flux::modal('delete-user-modal')->show();
    }

    /**
     * Eksekusi penghapusan akun setelah dikonfirmasi via modal
     */
    public function executeDeleteUser(): void
    {
        if (! $this->userToDeleteId) {
            return;
        }

        $user = User::findOrFail($this->userToDeleteId);

        if (Auth::id() === $user->id) {
            \Flux::modal('delete-user-modal')->close();
            $this->dispatch('toast-show', 
                slots: ['text' => 'Anda tidak dapat menghapus akun Anda sendiri.', 'heading' => 'Kesalahan'], 
                dataset: ['variant' => 'danger'], 
                duration: 0
            );
            return;
        }

        $this->authorize('delete', $user);

        $user->delete();

        $this->userToDeleteId = null;
        $this->userToDeleteName = null;

        \Flux::modal('delete-user-modal')->close();

        session()->flash('success', 'Pengguna berhasil dihapus.');
        $this->dispatch('toast-show', 
            slots: ['text' => 'Pengguna berhasil dihapus.', 'heading' => 'Sukses'], 
            dataset: ['variant' => 'success'], 
            duration: 5000
        );
    }

    /**
     * Kompatibilitas mundur untuk pemanggilan langsung (misal unit/feature test eksisting)
     */
    public function deleteUser(int $id): void
    {
        $this->userToDeleteId = $id;
        $this->executeDeleteUser();
    }

    public function render()
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles')->latest();

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        $users = $query->paginate($this->perPage);

        return view('livewire.user-table', [
            'users' => $users,
        ]);
    }
}
```

##### 2. Pembaruan Tombol Pemicu pada Tabel (`resources/views/livewire/user-table.blade.php`)
Ganti baris 79–88 dengan:
```blade
@if (auth()->id() !== $user->id)
    <flux:button 
        type="button" 
        variant="subtle"
        size="sm"
        square
        wire:click="confirmDeleteUser({{ $user->id }})" 
        title="Hapus Pengguna" 
        class="text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <flux:icon icon="trash" class="size-4" />
    </flux:button>
@endif
```

##### 3. Penempatan Komponen Modal Tunggal pada Root View (`resources/views/livewire/user-table.blade.php`)
Ditempatkan di bagian bawah wadah utama sebelum tag penutup `</div>`:
```blade
    {{-- Modal Konfirmasi Hapus Akun Pengguna --}}
    <flux:modal name="delete-user-modal" class="md:w-96">
        <div class="space-y-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2.5 text-rose-600 dark:text-rose-400">
                    <div class="size-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 flex items-center justify-center shrink-0">
                        <flux:icon icon="exclamation-triangle" class="size-4 text-rose-600 dark:text-rose-400" />
                    </div>
                    <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Hapus Akun Pengguna</flux:heading>
                </div>

                <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    Apakah Anda yakin ingin menghapus akun pengguna <strong class="text-zinc-900 dark:text-white font-semibold">{{ $userToDeleteName ?? 'ini' }}</strong>? Pengguna tidak akan dapat mengakses sistem kembali.
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2 mt-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="executeDeleteUser" wire:loading.attr="disabled">
                    Hapus Pengguna
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
```

---

## 4. Benchmark Reference Architecture: Existing Flux Modal in SIAP-AI

Sistem SIAP-AI telah memiliki implementasi modal Flux UI yang berfungsi penuh pada modul arsip utama:

### 4.1 Backend Controller (`app/Livewire/ArchiveTable.php`)
```php
public function confirmDelete($id = null): void
{
    if ($id) {
        $this->selected = [$id];
    }

    if (empty($this->selected)) {
        $this->dispatch('toast-show', slots: ['text' => 'Pilih minimal satu arsip untuk dihapus.'], dataset: ['variant' => 'warning']);
        return;
    }

    $this->deleteReason = '';
    \Flux::modal('delete-reason-modal')->show();
}

public function executeDelete(): void
{
    if (empty($this->selected)) {
        return;
    }

    if (empty(trim($this->deleteReason))) {
        $this->addError('deleteReason', 'Alasan penghapusan wajib diisi.');
        return;
    }

    $archives = Archive::whereIn('id', $this->selected)->get();
    
    foreach ($archives as $archive) {
        $this->authorize('delete', $archive);
        $archive->delete_reason = trim($this->deleteReason);
        $archive->save();
        $archive->delete();
    }

    $this->selected = [];
    $this->deleteReason = '';
    \Flux::modal('delete-reason-modal')->close();
    $this->dispatch('toast-show', slots: ['text' => 'Arsip berhasil dihapus beserta alasannya!', 'heading' => 'Sukses'], dataset: ['variant' => 'success']);
}
```

### 4.2 Blade View (`resources/views/livewire/archive-table.blade.php:189-214`)
```blade
    {{-- Delete Reason Modal --}}
    <flux:modal name="delete-reason-modal" class="md:w-96">
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Konfirmasi Penghapusan</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Silakan masukkan alasan penghapusan arsip ini. Alasan ini akan tercatat dalam riwayat (Trash).
            </p>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Alasan Hapus</label>
                <textarea 
                    wire:model="deleteReason" 
                    rows="3" 
                    class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                    placeholder="Contoh: Dokumen ganda, sudah kedaluwarsa..."></textarea>
                @error('deleteReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="executeDelete()" wire:loading.attr="disabled">Hapus Arsip</flux:button>
            </div>
        </div>
    </flux:modal>
```

Karakteristik keunggulan pola benchmark ini:
1. Pemanggilan modal terprogram via `\Flux::modal('...')->show()` dan `->close()` dari sisi server.
2. Penanganan state formulir reaktif (`wire:model="deleteReason"`).
3. Proteksi aksi ganda (*double submission prevention*) menggunakan `wire:loading.attr="disabled"`.
4. Integrasi mulus dengan sistem notifikasi *toast* SIAP-AI (`window.SiapToast`).

---

## 5. Architectural Blueprints & Implementation Patterns

Untuk mengimplementasikan penggantian dialog konfirmasi secara konsisten di seluruh aplikasi, tim pengembang harus membedakan dua skenario arsitektur:

### Pattern 1: Komponen Reaktif Livewire (Direct PHP Component Dispatch)
**Skenario Penggunaan**: Digunakan pada komponen berbasis Livewire seperti `UserTable.php`.
- **Mekanisme Kerja**:
  1. Tombol pada baris tabel memicu aksi Livewire: `wire:click="confirmDeleteUser({{ $user->id }})"`.
  2. Komponen Livewire menyimpan ID dan nama model sasaran ke properti publik kelas (`$userToDeleteId`, `$userToDeleteName`).
  3. Komponen mengeksekusi `\Flux::modal('delete-user-modal')->show()`.
  4. Modal tunggal di-*render* di luar loop tabel, menampilkan data target secara reaktif.
  5. Tombol konfirmasi memanggil `wire:click="executeDeleteUser"`, menghapus data, menutup modal dengan `\Flux::modal('delete-user-modal')->close()`, dan mengirimkan event notifikasi *toast*.

### Pattern 2: Template Blade Standar Server-Rendered
**Skenario Penggunaan**: Digunakan pada halaman yang dikelola langsung oleh Controller tanpa Livewire, seperti `pages/archives/trash.blade.php`.

Terdapat dua variasi implementasi:

#### Pattern 2A: Row-Scoped Named Modals (Rekomendasi Utama untuk Tabel Standar)
- Setiap baris memiliki modal bernama unik: `<flux:modal name="force-delete-archive-{{ $archive->id }}">`.
- **Kelebihan**:
  - 100% murni Blade dan Flux UI deklaratif.
  - Bebas dari pengelolaan state JavaScript tambahan.
  - Sempurna untuk tabel terpaginasi dengan 10 rekaman per halaman (hanya menghasilkan 20 elemen modal tersembunyi yang ringan di DOM).

#### Pattern 2B: Single Dynamic Modal via Alpine.js (Alternatif untuk Tabel Kerapatan Sangat Tinggi)
- Jika tabel menampilkan lebih dari 50 baris per halaman, modal tunggal dapat digunakan dengan pembungkus state Alpine.js di level kontainer tabel:
```blade
<div x-data="{ 
    modalAction: '', 
    modalMethod: 'POST', 
    modalTitle: '', 
    modalDocNumber: '', 
    modalWarning: '', 
    openModal(action, method, title, docNumber, warning) {
        this.modalAction = action;
        this.modalMethod = method;
        this.modalTitle = title;
        this.modalDocNumber = docNumber;
        this.modalWarning = warning;
        $dispatch('modal-show', { name: 'trash-confirm-modal' });
    }
}">
    {{-- Tombol pemicu pada baris tabel --}}
    <flux:button 
        variant="subtle" 
        size="sm" 
        square 
        @click="openModal('{{ route('archives.restore', $archive->id) }}', 'POST', '{{ addslashes($archive->title) }}', '{{ $archive->document_number }}', '')">
        <flux:icon icon="arrow-uturn-left" class="size-4" />
    </flux:button>

    {{-- Modal Dinamis Tunggal di Luar Tabel --}}
    <flux:modal name="trash-confirm-modal" class="md:w-96">
        <form :action="modalAction" method="POST" class="space-y-4">
            @csrf
            <template x-if="modalMethod === 'DELETE'">
                <input type="hidden" name="_method" value="DELETE">
            </template>
            
            <div class="space-y-2">
                <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">
                    <span x-text="modalMethod === 'DELETE' ? 'Hapus Arsip Permanen' : 'Pulihkan Arsip'"></span>
                </flux:heading>
                <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400">
                    Dokumen: <strong class="text-zinc-900 dark:text-white" x-text="modalTitle"></strong>
                </flux:subheading>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="subtle">Batal</flux:button>
                </flux:modal.close>
                <template x-if="modalMethod === 'DELETE'">
                    <flux:button variant="danger" type="submit">Hapus Selamanya</flux:button>
                </template>
                <template x-if="modalMethod !== 'DELETE'">
                    <flux:button variant="primary" type="submit">Pulihkan</flux:button>
                </template>
            </div>
        </form>
    </flux:modal>
</div>
```

### Standardisasi Komponen Sesuai SIAP-AI Guidelines
1. **Zero Emoji Policy**:
   - Dilarang keras menggunakan emoji (misal: ⚠️, 🗑️, ♻️, 🔥).
   - Seluruh ikon harus menggunakan `<flux:icon icon="..." />` resmi (Heroicons: `arrow-uturn-left`, `fire`, `trash`, `exclamation-triangle`, `pencil-square`).
2. **Kesesuaian Tombol (*Button Semantics*)**:
   - Tindakan pembatalan: `<flux:button variant="subtle">Batal</flux:button>`.
   - Tindakan penghapusan/bahaya: `<flux:button variant="danger">...</flux:button>`.
   - Tindakan pemulihan/positif: `<flux:button variant="primary" class="bg-emerald-600 hover:bg-emerald-500 text-white">...</flux:button>`.
3. **Pemberitahuan Reaktif (*Flash Feedback*)**:
   - Setiap operasi diakhiri dengan dispatch notifikasi toast: `$this->dispatch('toast-show', ...)` untuk Livewire atau flash session untuk request HTTP reguler.

---

## 6. Verification & Audit Execution Proof

### 6.1 Perintah Audit dan Pembuktian Temuan
Seluruh temuan dapat diverifikasi secara mandiri menggunakan utilitas CLI berikut pada direktori kerja repositori `/Users/brahmanda/Sites/siap-ai`:

#### 1. Verifikasi Pemanggilan `confirm()` pada Seluruh Template Blade
```bash
grep -rin "confirm(" resources/views/
```
**Keluaran Terverifikasi**:
```
resources/views/pages/archives/trash.blade.php:55: onclick="return confirm('Kembalikan dokumen ini ke daftar arsip aktif?')"
resources/views/pages/archives/trash.blade.php:66: onclick="return confirm('PERINGATAN: Tindakan ini akan menghapus arsip secara permanen dari sistem dan tidak dapat dibatalkan. Lanjutkan?')"
```
*(Hanya ditemukan pada `trash.blade.php` baris 55 dan 66).*

#### 2. Verifikasi Direktif `wire:confirm` pada Seluruh Template Blade
```bash
grep -rin "wire:confirm" resources/views/
```
**Keluaran Terverifikasi**:
```
resources/views/livewire/user-table.blade.php:83: wire:confirm="Yakin ingin menghapus akun pengguna '{{ $user->name }}'?"
```
*(Hanya ditemukan pada `user-table.blade.php` baris 83).*

#### 3. Verifikasi Aset JavaScript (`resources/js/`)
```bash
grep -rin "confirm" resources/js/
```
**Keluaran Terverifikasi**:
```
(No results found - 0 kemunculan)
```
*(Membuktikan tidak ada pemanggilan `confirm` atau `window.confirm` tersembunyi di dalam bundel JavaScript).*

#### 4. Verifikasi Keberadaan Benchmark Modal Eksisting (`archive-table.blade.php`)
```bash
grep -rin "flux:modal" resources/views/livewire/archive-table.blade.php
```
**Keluaran Terverifikasi**:
```
190:    <flux:modal name="delete-reason-modal" class="md:w-96">
208:                <flux:modal.close>
214:    </flux:modal>
```

#### 5. Pemilahan Hasil Negatif (*Exclusion of Non-Dialog Occurrences*)
Pencarian teks `confirm` pada `resources/views/pages/users/create.blade.php` (baris 47) dan `resources/views/pages/users/edit.blade.php` (baris 49) menghasilkan atribut `name="password_confirmation"`. Ini adalah elemen input form otentikasi standar dan bukan dialog interaktif.

### 6.2 Integritas Kode Sumber (Zero Source Code Changes)
Status repositori git diverifikasi untuk menjamin kepatuhan mutlak terhadap aturan bahwa fase audit ini tidak melakukan manipulasi terhadap berkas kode sumber aplikasi (`app/`, `resources/views/`, `resources/js/`, `routes/`):
```bash
git status --porcelain
```
Seluruh direktori kode sumber aplikasi tetap dalam keadaan bersih (*unmodified*). Dokumen audit ini dan metadata agen merupakan satu-satunya berkas yang dihasilkan.
