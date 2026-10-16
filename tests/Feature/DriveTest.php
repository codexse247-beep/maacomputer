<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DriveController;
use App\Models\DriveItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'অ্যাডমিন', 'email' => 'a@example.com', 'password' => 'secret-pass']);
        $this->actingAs($this->admin, 'web');
    }

    private function disposition(string $name): string
    {
        return \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition('attachment', $name, preg_replace('/[^A-Za-z0-9 ._()-]/', '_', \Illuminate\Support\Str::ascii($name)));
    }

    /** Send $content in pieces like the browser does; returns the last response. */
    private function uploadInPieces(string $name, string $content, int $piece, array $extra = [])
    {
        $id = 'test-'.md5($name.$content);
        $offset = 0;
        do {
            $part = substr($content, $offset, $piece);
            $response = $this->post('/admin/drive/upload', $extra + [
                'upload_id' => $id, 'name' => $name, 'size' => strlen($content), 'offset' => $offset,
                'chunk' => UploadedFile::fake()->createWithContent('piece', $part),
            ], ['Accept' => 'application/json'])->assertOk();
            $offset += strlen($part);
        } while ($offset < strlen($content));

        return $response;
    }

    public function test_pages_load_for_admin_only(): void
    {
        foreach (['/admin/drive', '/admin/drive?view=recent', '/admin/drive?view=starred', '/admin/drive?view=trash', '/admin/drive?q=x', '/admin/drive?sort=size&dir=desc'] as $url) {
            $this->get($url)->assertOk();
        }
        $teacher = User::create(['name' => 'শিক্ষক', 'email' => 't@example.com', 'password' => 'secret-pass', 'role' => 'teacher']);
        $this->actingAs($teacher, 'web')->get('/admin/drive')->assertForbidden();
    }

    public function test_upload_in_pieces_then_download(): void
    {
        $content = str_repeat('মা কম্পিউটার ', 500);
        $this->uploadInPieces('নোট.txt', $content, 1000)->assertJson(['done' => true]);

        $item = DriveItem::sole();
        $this->assertSame('নোট.txt', $item->name);
        $this->assertSame(strlen($content), $item->size);
        $this->assertSame('text/plain', $item->mime);
        Storage::disk('local')->assertExists($item->path);
        $this->assertSame($content, Storage::disk('local')->get($item->path));
        $this->assertSame([], Storage::disk('local')->files(DriveItem::TMP));

        $this->get("/admin/drive/{$item->id}/download")->assertOk()->assertHeader('Content-Disposition', $this->disposition('নোট.txt'));
        $this->get('/admin/drive')->assertSee('নোট.txt');
    }

    public function test_a_missing_piece_is_reported_with_the_right_offset(): void
    {
        $this->post('/admin/drive/upload', ['upload_id' => 'abcdefgh1', 'name' => 'a.txt', 'size' => 10, 'offset' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('piece', 'abcd')], ['Accept' => 'application/json'])->assertOk()->assertJson(['offset' => 4]);
        $this->post('/admin/drive/upload', ['upload_id' => 'abcdefgh1', 'name' => 'a.txt', 'size' => 10, 'offset' => 8,
            'chunk' => UploadedFile::fake()->createWithContent('piece', 'ij')], ['Accept' => 'application/json'])->assertStatus(409)->assertJson(['offset' => 4]);
        $this->assertSame(0, DriveItem::count());
    }

    public function test_empty_file_and_folder_upload_creates_folders(): void
    {
        $this->uploadInPieces('empty.txt', '', 10, ['relative' => 'Photos/2026'])->assertJson(['done' => true]);
        $this->uploadInPieces('b.txt', 'bb', 10, ['relative' => 'Photos/2026']);

        $photos = DriveItem::where('name', 'Photos')->sole();
        $year = DriveItem::where('name', '2026')->sole();
        $this->assertTrue($photos->is_folder);
        $this->assertSame($photos->id, $year->parent_id);
        $this->assertSame(2, DriveItem::where('parent_id', $year->id)->count());
    }

    public function test_folders_rename_move_star_and_zip(): void
    {
        $this->post('/admin/drive/folders', ['name' => 'হিসাব'])->assertRedirect();
        $folder = DriveItem::where('is_folder', true)->sole();
        $this->uploadInPieces('report.pdf', '%PDF-1.4 test', 100, ['folder' => $folder->id]);
        $file = DriveItem::where('is_folder', false)->sole();
        $this->assertSame($folder->id, $file->parent_id);

        $this->put("/admin/drive/{$file->id}", ['name' => 'মাসিক রিপোর্ট'])->assertRedirect();
        $this->assertSame('মাসিক রিপোর্ট.pdf', $file->fresh()->name);

        $this->post("/admin/drive/{$file->id}/star")->assertRedirect();
        $this->assertTrue($file->fresh()->starred);
        $this->get('/admin/drive?view=starred')->assertSee('মাসিক রিপোর্ট.pdf');

        // A folder can't go into itself
        $this->post('/admin/drive/move', ['ids' => [$folder->id], 'target' => $folder->id])->assertSessionHasErrors('target');
        $this->post('/admin/drive/move', ['ids' => [$file->id], 'target' => null])->assertRedirect();
        $this->assertNull($file->fresh()->parent_id);

        $this->get("/admin/drive/{$folder->id}/download")->assertOk()->assertHeader('Content-Disposition', $this->disposition('হিসাব.zip'));
        $this->post('/admin/drive/zip', ['ids' => [$folder->id, $file->id]])->assertOk()->assertDownload();
    }

    public function test_trash_restore_and_delete_forever(): void
    {
        $this->post('/admin/drive/folders', ['name' => 'পুরনো']);
        $folder = DriveItem::sole();
        $this->uploadInPieces('x.txt', 'xx', 10, ['folder' => $folder->id]);
        $file = DriveItem::where('is_folder', false)->sole();

        $this->post('/admin/drive/trash', ['ids' => [$folder->id]])->assertRedirect();
        $this->assertSame(0, DriveItem::count());
        $this->assertSame(2, DriveItem::onlyTrashed()->count());
        // Only the folder is listed in the trash; its file comes back with it.
        $this->get('/admin/drive?view=trash')->assertSee('পুরনো')->assertDontSee('x.txt');

        $this->post('/admin/drive/restore', ['ids' => [$folder->id]])->assertRedirect();
        $this->assertSame(2, DriveItem::count());

        $this->post('/admin/drive/trash', ['ids' => [$folder->id]]);
        $this->delete('/admin/drive/forever', ['ids' => [$folder->id]])->assertRedirect();
        $this->assertSame(0, DriveItem::withTrashed()->count());
        Storage::disk('local')->assertMissing($file->path);
    }

    public function test_old_trash_is_emptied_by_the_nightly_clean(): void
    {
        $this->uploadInPieces('old.txt', 'o', 10);
        $item = DriveItem::sole();
        $this->post('/admin/drive/trash', ['ids' => [$item->id]]);
        DriveItem::onlyTrashed()->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('drive:clean')->assertSuccessful();
        $this->assertSame(0, DriveItem::withTrashed()->count());
        Storage::disk('local')->assertMissing($item->path);
    }

    public function test_share_link_downloads_without_login_until_turned_off(): void
    {
        $this->uploadInPieces('ফর্ম.pdf', '%PDF-1.4 x', 100);
        $item = DriveItem::sole();
        $this->post("/admin/drive/{$item->id}/share")->assertRedirect();
        $token = $item->fresh()->share_token;
        $this->assertSame(32, strlen($token));

        auth('web')->logout();
        $this->get("/d/$token")->assertOk()->assertHeader('Content-Disposition', $this->disposition('ফর্ম.pdf'));

        $this->actingAs($this->admin, 'web')->post("/admin/drive/{$item->id}/share", ['off' => 1]);
        auth('web')->logout();
        $this->get("/d/$token")->assertNotFound();
    }

    public function test_only_safe_types_open_in_the_browser(): void
    {
        $this->uploadInPieces('page.html', '<script>alert(1)</script>', 100);
        $item = DriveItem::sole();
        $this->assertFalse($item->canOpen());
        $this->get("/admin/drive/{$item->id}/open")->assertDownload('page.html');
    }

    public function test_upload_refused_when_the_disk_would_be_too_full(): void
    {
        $this->post('/admin/drive/upload', ['upload_id' => 'bigfile01', 'name' => 'big.zip', 'size' => DriveController::MAX_FILE + 1, 'offset' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('piece', 'x')], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
