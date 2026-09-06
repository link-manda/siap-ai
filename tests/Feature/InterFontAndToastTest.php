<?php

use App\Livewire\ArchiveTable;
use App\Livewire\UserTable;
use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('standardizes typography on inter across login and authenticated layouts', function () {
    $user = User::factory()->create();

    // Test fullscreen / login layout
    $loginResponse = $this->get(route('login'));
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee('family=inter:400,500,600,700', false);
    $loginResponse->assertDontSee('instrument-sans', false);
    $loginResponse->assertDontSee('Outfit', false);

    // Test authenticated app layout
    $appResponse = $this->actingAs($user)->get(route('dashboard'));
    $appResponse->assertStatus(200);
    $appResponse->assertSee('family=inter:400,500,600,700', false);
    $appResponse->assertDontSee('instrument-sans', false);
    $appResponse->assertDontSee('Outfit', false);
});

it('verifies css configuration has inter font and puskesmas green accent theme', function () {
    $cssPath = resource_path('css/app.css');
    $cssContent = file_get_contents($cssPath);

    // Font verification
    expect($cssContent)->not->toContain('Outfit');
    expect($cssContent)->toContain("--font-sans: 'Inter', sans-serif;");

    // Puskesmas Green Theme Accent verification
    expect($cssContent)->toContain('--color-accent: var(--color-green-600);');
    expect($cssContent)->toContain('--color-accent-content: var(--color-green-600);');
    expect($cssContent)->toContain('--color-accent-foreground: var(--color-white);');
    expect($cssContent)->toContain('--color-accent-content: var(--color-green-400);');
});

it('renders global flux toast container in both layouts', function () {
    $user = User::factory()->create();

    $loginResponse = $this->get(route('login'));
    $loginResponse->assertStatus(200);
    $loginResponse->assertSee('<ui-toast', false);
    $loginResponse->assertSee('position="top right"', false);

    $appResponse = $this->actingAs($user)->get(route('dashboard'));
    $appResponse->assertStatus(200);
    $appResponse->assertSee('<ui-toast', false);
    $appResponse->assertSee('position="top right"', false);
});

it('bridges session success flash to flux toast with 5000ms duration', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['success' => 'Arsip berhasil disimpan!'])
        ->get(route('archives.index'));

    $response->assertStatus(200);
    $response->assertSee('window.SiapToast', false);
    $response->assertSee('Arsip berhasil disimpan!', false);
    $response->assertSee('success', false);
    $response->assertSee('5000', false);
});

it('bridges session error flash to persistent danger flux toast with zero duration', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['error' => 'Gagal memproses dokumen.'])
        ->get(route('archives.index'));

    $response->assertStatus(200);
    $response->assertSee('window.SiapToast', false);
    $response->assertSee('Gagal memproses dokumen.', false);
    $response->assertSee('danger', false);
    $response->assertSee('Kesalahan', false);
    $response->assertSee(', 0);', false);
});

it('verifies livewire tables no longer contain legacy inline session banners', function () {
    $archiveTableView = file_get_contents(resource_path('views/livewire/archive-table.blade.php'));
    $userTableView = file_get_contents(resource_path('views/livewire/user-table.blade.php'));

    expect($archiveTableView)->not->toContain("session('success')");
    expect($archiveTableView)->not->toContain("session()->has('success')");

    expect($userTableView)->not->toContain("session('success')");
    expect($userTableView)->not->toContain("session('error')");
    expect($userTableView)->not->toContain("session()->has('success')");
    expect($userTableView)->not->toContain("session()->has('error')");
});

it('dispatches reactive toast-show event when deleting archive in livewire archive table', function () {
    $role = Role::firstOrCreate(['name' => 'Administrator']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $category = Category::create(['name' => 'Umum']);
    $classification = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $archive = Archive::create([
        'category_id' => $category->id,
        'classification_id' => $classification->id,
        'user_id' => $user->id,
        'title' => 'Arsip Hapus Test',
        'file_path' => 'private/archives/test.pdf',
        'file_type' => 'pdf',
    ]);

    Livewire::actingAs($user)
        ->test(ArchiveTable::class)
        ->call('deleteArchive', $archive->id)
        ->assertDispatched('toast-show');

    expect(Archive::find($archive->id))->toBeNull();
});

it('dispatches reactive toast-show event when deleting user in livewire user table', function () {
    $role = Role::firstOrCreate(['name' => 'Pimpinan']);
    $role->givePermissionTo('manage-users');

    $admin = User::factory()->create();
    $admin->assignRole($role);

    $targetUser = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->call('deleteUser', $targetUser->id)
        ->assertDispatched('toast-show');

    expect(User::find($targetUser->id))->toBeNull();
});

it('dispatches persistent danger toast when user attempts self deletion in livewire user table', function () {
    $role = Role::firstOrCreate(['name' => 'Pimpinan']);
    $role->givePermissionTo('manage-users');

    $admin = User::factory()->create();
    $admin->assignRole($role);

    Livewire::actingAs($admin)
        ->test(UserTable::class)
        ->call('deleteUser', $admin->id)
        ->assertDispatched('toast-show');

    expect(User::find($admin->id))->not->toBeNull();
});

it('flashes session success on bulk store for redirect toast presentation', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Umum']);
    $classification = Classification::create(['name' => 'Biasa', 'level' => 1]);

    $file = UploadedFile::fake()->create('doc1.pdf', 100, 'application/pdf');
    $path = $file->storeAs('temp/archives', 'doc1.pdf', 'local');

    $response = $this->actingAs($user)->postJson(route('archives.bulk-store'), [
        'archives' => [
            [
                'title' => 'Bulk Doc Test',
                'file_path' => $path,
                'file_type' => 'pdf',
                'category_id' => $category->id,
                'classification_id' => $classification->id,
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertSessionHas('success', 'Semua arsip berhasil disimpan!');
});
