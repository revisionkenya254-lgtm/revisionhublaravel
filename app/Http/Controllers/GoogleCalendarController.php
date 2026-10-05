<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GoogleCalendarController extends Controller {
    public function __construct() {
        $this->middleware(function ($request, $next) {
            $setting = Cache::get('setting');
            abort_if(!$setting || $setting?->google_calendar_status !== 'active',404);
            return $next($request);
        });
    }
    public function index(): View {
        return view('frontend.instructor-dashboard.calendar.index');
    }
    private function client() {
        $setting = Cache::get('setting');

        $client = new Client();
        $client->setClientId($setting?->gmail_client_id);
        $client->setClientSecret($setting?->gmail_secret_id);
        $client->setRedirectUri(route('instructor.google-calendar.callback'));
        $client->setScopes(['https://www.googleapis.com/auth/calendar']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        return $client;
    }

    public function redirect() {
        return redirect($this->client()->createAuthUrl());
    }

    public function callback(Request $request) {
        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        auth('web')->user()->update([
            'google_access_token'  => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? null,
        ]);

        return redirect()->route('instructor.google-calendar.index')
            ->with('messege', 'Google Calendar connected successfully', 'alert-type', 'success');
    }

    public function disconnect(Request $request) {
        auth('web')->user()->update([
            'google_access_token'  => null,
            'google_refresh_token' => null,
        ]);

        return response()->json(['status' => 'success', 'message' => __('Google Calendar disconnected successfully')]);
    }
}
