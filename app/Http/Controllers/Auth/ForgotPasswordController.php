<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Services\Access\AccessApiService;
use App\Services\Access\Exceptions\AccessApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(
        ForgotPasswordRequest $request,
        AccessApiService $accessApi,
    ): RedirectResponse {
        try {
            $message = $accessApi->forgotPassword(
                $request->string('email')->toString(),
                route('login'),
            );
        } catch (AccessApiException) {
            return back()
                ->withInput()
                ->with('auth_error', 'No se pudo procesar la solicitud. Intenta nuevamente.');
        }

        return back()->with('status', $message);
    }
}
