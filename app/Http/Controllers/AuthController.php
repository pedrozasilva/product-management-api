<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $tokens = $this->authService->createTokenPair($user, 'register');

        return $this->success([
            'user' => $user,
            'tokens' => $tokens,
        ], 'Usuário registrado com sucesso.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->attemptLogin(
            $request->email,
            $request->password,
        );

        if (! $user) {
            return $this->error('Credenciais inválidas.', code: 401);
        }

        $tokens = $this->authService->createTokenPair($user, 'login');

        return $this->success([
            'user' => $user,
            'tokens' => $tokens,
        ], 'Login realizado com sucesso.');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $tokens = $this->authService->refreshTokens($request->refresh_token);

        if (! $tokens) {
            return $this->error('Refresh token inválido ou expirado.', code: 401);
        }

        return $this->success([
            'tokens' => $tokens,
        ], 'Tokens renovados com sucesso.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logoutCurrentDevice($request->user());

        return $this->success(message: 'Logout realizado com sucesso.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(message: 'Logout realizado em todos os dispositivos.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($request->user(), 'Usuário autenticado.');
    }
}
