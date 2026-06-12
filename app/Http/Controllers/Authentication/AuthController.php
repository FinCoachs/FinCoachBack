<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // use dynamic where to avoid custom where signature issues
        $user = User::whereEmail($request->email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    public function updateProfil(Request $request)
    {
        $request->validate(['profil' => 'nullable|string']);
        $request->user()->update(['profil' => $request->profil]);
        return response()->json(['success' => true]);
    }

    public function updateInfo(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $request->user()->update($request->only('name', 'email'));

        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()->fresh()),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'profil'                => 'nullable|string',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $request->password,
            'profil'            => $request->profil,
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => new UserResource($user),
        ], 201);
    }
}
