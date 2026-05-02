<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Upload or replace the authenticated user's profile picture.
     *
     * The image is stored on the configured default disk (S3 in production,
     * "public" locally).  The previous picture, if any, is deleted first.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $disk = $this->resolveDisk();

        // Remove the old picture so we do not accumulate orphaned files.
        if ($user->profile_picture) {
            Storage::disk($disk)->delete($user->profile_picture);
        }

        $path = $request->file('avatar')->store('avatars', $disk);

        $user->update(['profile_picture' => $path]);

        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'url' => $user->profilePictureUrl(),
        ]);
    }

    /**
     * Return the authenticated user's profile picture URL.
     */
    public function showAvatar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'url' => $user->profilePictureUrl(),
        ]);
    }

    /**
     * Delete the authenticated user's profile picture.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->profile_picture) {
            Storage::disk($this->resolveDisk())->delete($user->profile_picture);
            $user->update(['profile_picture' => null]);
        }

        return response()->json(['message' => 'Profile picture removed.']);
    }

    private function resolveDisk(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'public';
    }
}
