<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255|unique:users,name',
                'password' => 'required|string|min:4|confirmed',
        ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données d\'inscription invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->username,
                'email' => $request->username . '@local.fake',
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'inscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données de connexion invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('name', $request->login)
                       ->orWhere('email', $request->login)
                       ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Identifiants incorrects'
                ], 401);
        }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
    }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'username' => $user->name,
                    'created_at' => $user->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {       
            return response()->json([
                'message' => 'Erreur lors de la récupération des informations utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
