<?php

namespace App\Services;

use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Cache;

class GoogleCalendarService {
    protected Client $client;
    protected Calendar $calendar;
    protected $setting;
    protected $owner;

    public static function forInstructor($instructor): self {
        return new self($instructor);
    }

    /**
     * @param $instructor
     */
    private function __construct($instructor) {
        $this->owner = $instructor;
        $this->setting = Cache::get('setting');

        $this->client = new Client();
        $this->client->setClientId($this->setting?->gmail_client_id);
        $this->client->setClientSecret($this->setting?->gmail_secret_id);
        $this->client->setRedirectUri(route('instructor.google-calendar.callback'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setScopes([Calendar::CALENDAR_EVENTS]);

        if ($instructor->google_access_token) {
            $this->client->setAccessToken($instructor->google_access_token);

            if (
                $this->client->isAccessTokenExpired() &&
                $instructor->google_refresh_token
            ) {
                try {
                    $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                        $instructor->google_refresh_token
                    );
                    // auto-save refreshed token
                    $instructor->update([
                        'google_access_token' => $newToken,
                    ]);
                } catch (\Exception $e) {
                    info('Google token refresh failed', [
                        'instructor_id' => $instructor->id,
                    ]);
                }
            }
        }

        $this->calendar = new Calendar($this->client);
    }

    /**
     * =========================
     * CREATE EVENT
     * =========================
     */
    public function createEvent(array $payload): ?string {
        try {
            $eventData = $this->buildEventPayload($payload);

            $event = new Event($eventData);

            $created = $this->calendar->events->insert(
                'primary',
                $event,
                ['conferenceDataVersion' => 1]
            );

            return $created->getId();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * =========================
     * UPDATE EVENT
     * =========================
     */
    public function updateEvent(string $eventId, array $payload): bool {
        try {
            $event = $this->calendar->events->get('primary', $eventId);

            if (isset($payload['summary'])) {
                $event->setSummary($payload['summary']);
            }

            if (!empty($payload['description'])) {
                $event->setDescription($payload['description']);
            }

            if (!empty($payload['location'])) {
                $event->setLocation($payload['location']);
            }

            if (!empty($payload['start'])) {
                $event->setStart(new EventDateTime([
                    'dateTime' => $this->toRFC3339($payload['start']),
                    'timeZone' => $this->setting?->timezone ?? 'UTC',
                ]));
            }

            if (!empty($payload['end'])) {
                $event->setEnd(new EventDateTime([
                    'dateTime' => $this->toRFC3339($payload['end']),
                    'timeZone' => $this->setting?->timezone ?? 'UTC',
                ]));
            }

            $this->calendar->events->update('primary', $eventId, $event);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * =========================
     * DELETE EVENT
     * =========================
     */
    public function deleteEvent(string $eventId): bool {
        try {
            $this->calendar->events->delete('primary', $eventId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * =========================
     * ADD TO GOOGLE CALENDAR LINK (STUDENTS)
     * =========================
     */
    public function generateAddToCalendarLink(
        string $summary,
        string $description,
        string $start,
        string $end,
        ?string $location = null
    ): string {
        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => $summary,
            'details'  => $description,
            'dates'    => $this->toUTC($start) . '/' . $this->toUTC($end),
            'location' => $location,
        ]);
    }

    /**
     * =========================
     * INTERNAL HELPERS
     * =========================
     */
    protected function buildEventPayload(array $payload): array {
        $event = [
            'summary' => $payload['summary'],
            'start'   => [
                'dateTime' => $this->toRFC3339($payload['start']),
                'timeZone' => $this->setting?->timezone ?? 'UTC',
            ],
            'end'     => [
                'dateTime' => $this->toRFC3339($payload['end']),
                'timeZone' => $this->setting?->timezone ?? 'UTC',
            ],
        ];

        if (!empty($payload['description'])) {
            $event['description'] = $payload['description'];
        }

        if (!empty($payload['location'])) {
            $event['location'] = $payload['location'];
        }

        if (!empty($payload['attendees'])) {
            $event['attendees'] = $payload['attendees'];
        }

        if (!empty($payload['meet'])) {
            $event['conferenceData'] = [
                'createRequest' => [
                    'requestId'             => uniqid(),
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ];
        }

        return $event;
    }

    protected function toRFC3339(string $dateTime): string {
        $carbon = Carbon::parse($dateTime, 'Asia/Dhaka');
        return $carbon->format('c');
    }

    protected function toUTC(string $dateTime): string {
        return Carbon::parse($dateTime)->utc()->format('Ymd\THis\Z');
    }
}
