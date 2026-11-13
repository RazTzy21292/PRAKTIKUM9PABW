<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Buku\Models\Buku;

function dataBuku(array $overrides = []): array
{
    return [
        'judul' => 'Laravel untuk Pemula',
        'penulis' => 'Budi Santoso',
        'penerbit' => 'Informatika Press',
        'tahun_terbit' => 2023,
        'stok' => 5,
        ...$overrides,
    ];
}

test('user can see the book list but cannot manage books', function () {
    Buku::factory()->count(3)->create();

    $this->actingAs(User::factory()->create());

    $this->get('/buku')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Buku::index', false)->has('bukus.data', 3));

    $this->get('/buku/create')->assertForbidden();
    $this->post('/buku', dataBuku())->assertForbidden();
});

test('book list can be searched', function () {
    Buku::factory()->create(['judul' => 'Pemrograman React']);
    Buku::factory()->create(['judul' => 'Basis Data']);

    $this->actingAs(User::factory()->create())
        ->get('/buku?search=React')
        ->assertInertia(fn (Assert $page) => $page->has('bukus.data', 1));
});

test('book list is paginated', function () {
    Buku::factory()->count(12)->create();

    $this->actingAs(User::factory()->create())
        ->get('/buku')
        ->assertInertia(fn (Assert $page) => $page->has('bukus.data', 10)->where('bukus.total', 12));
});

test('admin can create, update, and delete a book', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post('/buku', dataBuku())
        ->assertRedirect('/buku')
        ->assertSessionHas('success');

    $buku = Buku::first();

    $this->put("/buku/{$buku->id}", dataBuku(['stok' => 9]))
        ->assertRedirect('/buku')
        ->assertSessionHas('success');

    expect($buku->fresh()->stok)->toBe(9);

    $this->delete("/buku/{$buku->id}")->assertRedirect('/buku');
    $this->assertDatabaseMissing('bukus', ['id' => $buku->id]);
});

test('book form validation returns errors', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/buku', [])
        ->assertSessionHasErrors(['judul', 'penulis', 'penerbit', 'tahun_terbit', 'stok']);
});
