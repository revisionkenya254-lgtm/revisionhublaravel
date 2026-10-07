<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\Ai\AiDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountDeletionController extends Controller
{
    public function showRequestForm(): View
    {
        return view('frontend.account-deletion.request');
    }

    public function requestByEmail(Request $request, AccountDeletionService $deletion): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])
            ->whereIn('role', ['student', 'instructor'])
            ->first();

        if ($user) {
            $deletion->sendConfirmation($user);
        }

        return back()->with('status', __('If an account matches that email address, we have sent a confirmation link.'));
    }

    public function requestAuthenticated(Request $request, AccountDeletionService $deletion): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->role, ['student', 'instructor'], true), 403);

        $deletion->sendConfirmation($user);

        return back()->with('status', __('A confirmation link has been sent to your account email address.'));
    }

    public function requestApi(Request $request, AccountDeletionService $deletion): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->role, ['student', 'instructor'], true), 403);

        $deletion->sendConfirmation($user);

        return response()->json([
            'status' => 'success',
            'message' => 'A confirmation link has been sent to your account email address. Confirm from the email to complete deletion.',
        ], 202);
    }

    public function showConfirmation(Request $request, int $user, string $email_hash): View
    {
        $account = User::whereKey($user)
            ->whereIn('role', ['student', 'instructor'])
            ->firstOrFail();

        abort_unless(hash_equals(
            hash_hmac('sha256', $account->email, (string) config('app.key')),
            $email_hash
        ), 403);

        return view('frontend.account-deletion.confirm', [
            'account' => $account,
            'confirmationUrl' => $request->fullUrl(),
        ]);
    }

    public function confirm(
        Request $request,
        int $user,
        string $email_hash,
        AccountDeletionService $deletion,
        AiDocumentService $aiDocumentService
    ): RedirectResponse {
        $account = User::whereKey($user)
            ->whereIn('role', ['student', 'instructor'])
            ->firstOrFail();

        abort_unless(hash_equals(
            hash_hmac('sha256', $account->email, (string) config('app.key')),
            $email_hash
        ), 403);

        $deletion->delete($account, $aiDocumentService);

        if (Auth::guard('web')->id() === $account->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('home')->with('status', __('Your account deletion request has been completed.'));
    }
}
