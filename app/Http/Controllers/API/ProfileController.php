<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;


class ProfileController extends Controller
{
    public function getUserByToken(Request $request)
    {
        try {
            // Mendapatkan token dari permintaan HTTP
            $token = $request->bearerToken();

            // Mengotentikasi pengguna berdasarkan token
            if (!$token) {
                throw new \Exception('Token not provided', 401);
            }

            $user = User::where('remember_token', $token)->first();

            if (!$user) {
                throw new \Exception('Unauthorized', 401);
            }

            // Mengembalikan data pengguna
            return response()->json($user, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
    }
    public function updateUserData(Request $request)
    {
        try {
            // Mendapatkan Bearer Token dari header Authorization
            $bearerToken = $request->bearerToken();

            // Validasi Bearer Token
            if (!$bearerToken) {
                throw ValidationException::withMessages([
                    'Authorization' => ['Invalid or missing bearer token.'],
                ]);
            }

            // Mendapatkan remember_token dari Bearer Token
            $rememberToken = str_replace('Bearer ', '', $bearerToken);

            // Validasi permintaan
            $validatedData = $request->validate([
                'name' => 'string',
                'email' => 'email',
            ]);

            // Mendapatkan pengguna berdasarkan remember_token
            $user = User::where('remember_token', $rememberToken)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'Authorization' => ['Invalid or expired token.'],
                ]);
            }

            // Memperbarui data pengguna
            $user->update($validatedData);

            // Mengembalikan respons berhasil
            return response()->json(['message' => 'User data updated successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function updateProfilePicture(Request $request)
    {
        try {
            // Mendapatkan token dari header Authorization
            $token = $request->bearerToken();

            // Validasi token
            if (!$token) {
                throw ValidationException::withMessages([
                    'Authorization' => ['Invalid or missing bearer token.'],
                ]);
            }

            // Mendapatkan pengguna berdasarkan token
            $user = User::where('remember_token', $token)->first();

            if (!$user) {
                throw ValidationException::withMessages([
                    'Authorization' => ['Invalid or expired token.'],
                ]);
            }

            // Validasi permintaan
            $validatedData = $request->validate([
                'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Gambar dengan maksimum 2MB
            ]);

            // Menghapus foto lama jika ada
            if ($user->foto) {
                $oldFotoPath = public_path('fotoprofile/' . $user->foto);
                if (File::exists($oldFotoPath)) {
                    File::delete($oldFotoPath);
                }
            }

            // Membuat nama file yang unik berdasarkan timestamp
            $fotoName = time() . '_' . $request->file('foto')->getClientOriginalName();

            // Memindahkan foto baru ke public/fotoprofile dengan nama unik
            $request->file('foto')->move(public_path('fotoprofile'), $fotoName);

            // Mengupdate kolom foto
            $user->foto = $fotoName;
            $user->save();

            // Mengembalikan respons berhasil
            return response()->json(['message' => 'Profile picture updated successfully'], 200);
        } catch (ValidationException $e) {
            return response()->json($e->errors(), 422);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
