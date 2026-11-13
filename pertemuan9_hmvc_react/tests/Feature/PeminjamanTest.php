<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Peminjaman\Models\Peminjaman;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
});

function dataPeminjaman(array $overrides = []): array
{
    return [
        'nama_peminjam' => 'Budi Santoso',
        'judul_buku' => 'Laravel untuk Pemula',
        'tanggal_pinjam' => '2026-01-10',
        'tanggal_kembali' => '2026-01-17',
        'status' => 'Dipinjam',
        ...$overrides,
    ];
}

test('index renders the react page of the module', function () {
    $this->get('/peminjaman')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Peminjaman::index', false));
});

test('data endpoint returns loans as json', function () {
    Peminjaman::factory()->count(3)->create();

    $this->getJson('/peminjaman/data')->assertOk()->assertJsonCount(3);
});

test('store returns 422 when the form is empty', function () {
    $this->postJson('/peminjaman', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nama_peminjam', 'judul_buku', 'tanggal_pinjam', 'tanggal_kembali', 'status', 'berkas']);
});

test('return date cannot be before the loan date', function () {
    $this->postJson('/peminjaman', dataPeminjaman([
        'tanggal_kembali' => '2026-01-01',
        'berkas' => UploadedFile::fake()->create('bukti.png', 100, 'image/png'),
    ]))->assertStatus(422)->assertJsonValidationErrors('tanggal_kembali');
});

test('store saves the data and the uploaded file', function () {
    $this->postJson('/peminjaman', dataPeminjaman([
        'berkas' => UploadedFile::fake()->create('bukti_budi.png', 100, 'image/png'),
    ]))->assertOk()->assertJson(['success' => 'Data berhasil disimpan.']);

    $peminjaman = Peminjaman::first();

    expect($peminjaman->nama_berkas)->toBe('bukti_budi.png');
    Storage::disk('public')->assertExists($peminjaman->berkas);
});

test('zip file is rejected', function () {
    $this->postJson('/peminjaman', dataPeminjaman([
        'berkas' => UploadedFile::fake()->create('berkas.zip', 100),
    ]))->assertStatus(422)->assertJsonValidationErrors('berkas');
});

test('update replaces the old file', function () {
    $this->postJson('/peminjaman', dataPeminjaman([
        'berkas' => UploadedFile::fake()->create('lama.png', 100, 'image/png'),
    ]));

    $peminjaman = Peminjaman::first();
    $oldPath = $peminjaman->berkas;

    $this->postJson("/peminjaman/{$peminjaman->id}", dataPeminjaman([
        '_method' => 'PUT',
        'status' => 'Dikembalikan',
        'berkas' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ]))->assertOk();

    Storage::disk('public')->assertMissing($oldPath);
    expect($peminjaman->fresh()->status)->toBe('Dikembalikan');
});

test('destroy deletes the data and its file, unknown id returns 404', function () {
    $this->postJson('/peminjaman', dataPeminjaman([
        'berkas' => UploadedFile::fake()->create('bukti.png', 100, 'image/png'),
    ]));

    $peminjaman = Peminjaman::first();

    $this->deleteJson("/peminjaman/{$peminjaman->id}")->assertOk();

    Storage::disk('public')->assertMissing($peminjaman->berkas);
    $this->assertDatabaseMissing('peminjamans', ['id' => $peminjaman->id]);
    $this->deleteJson('/peminjaman/999')->assertNotFound();
});
