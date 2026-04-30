<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Upload avatar
    // -----------------------------------------------------------------------

    public function test_authenticated_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->withToken($token)
            ->postJson('/api/profile/avatar', ['avatar' => $file]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'url'])
            ->assertJsonFragment(['message' => 'Profile picture updated successfully.']);

        $user->refresh();
        $this->assertNotNull($user->profile_picture);
        Storage::disk('public')->assertExists($user->profile_picture);
    }

    public function test_upload_replaces_previous_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['profile_picture' => 'avatars/old.jpg']);
        Storage::disk('public')->put('avatars/old.jpg', 'dummy');
        $token = $user->createToken('api')->plainTextToken;

        $file = UploadedFile::fake()->image('new.jpg', 200, 200);

        $this->withToken($token)
            ->postJson('/api/profile/avatar', ['avatar' => $file])
            ->assertOk();

        Storage::disk('public')->assertMissing('avatars/old.jpg');

        $user->refresh();
        $this->assertNotSame('avatars/old.jpg', $user->profile_picture);
    }

    public function test_upload_requires_an_image_file(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $this->withToken($token)
            ->postJson('/api/profile/avatar', ['avatar' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_upload_rejects_files_exceeding_2mb(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        // 3 MB fake image
        $file = UploadedFile::fake()->image('big.jpg')->size(3000);

        $this->withToken($token)
            ->postJson('/api/profile/avatar', ['avatar' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $this->postJson('/api/profile/avatar', ['avatar' => $file])
            ->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // Show avatar
    // -----------------------------------------------------------------------

    public function test_authenticated_user_can_view_their_avatar_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['profile_picture' => 'avatars/test.jpg']);
        Storage::disk('public')->put('avatars/test.jpg', 'dummy');
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/profile/avatar');

        $response->assertOk()
            ->assertJsonStructure(['url'])
            ->assertJsonFragment(['url' => $user->profilePictureUrl()]);
    }

    public function test_show_avatar_returns_null_url_when_no_picture_is_set(): void
    {
        $user = User::factory()->create(['profile_picture' => null]);
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/profile/avatar');

        $response->assertOk()
            ->assertJson(['url' => null]);
    }

    public function test_show_avatar_requires_authentication(): void
    {
        $this->getJson('/api/profile/avatar')
            ->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // Delete avatar
    // -----------------------------------------------------------------------

    public function test_authenticated_user_can_delete_their_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['profile_picture' => 'avatars/del.jpg']);
        Storage::disk('public')->put('avatars/del.jpg', 'dummy');
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->deleteJson('/api/profile/avatar');

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Profile picture removed.']);

        $user->refresh();
        $this->assertNull($user->profile_picture);
        Storage::disk('public')->assertMissing('avatars/del.jpg');
    }

    public function test_delete_avatar_is_idempotent_when_no_picture_is_set(): void
    {
        $user = User::factory()->create(['profile_picture' => null]);
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/profile/avatar')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Profile picture removed.']);
    }

    public function test_delete_avatar_requires_authentication(): void
    {
        $this->deleteJson('/api/profile/avatar')
            ->assertUnauthorized();
    }
}
