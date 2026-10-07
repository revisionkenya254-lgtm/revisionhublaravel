<?php

namespace App\Services;

use App\Models\AiDocument;
use App\Models\User;
use App\Services\Ai\AiDocumentService;
use App\Traits\MailSenderTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Assignment\app\Models\AssignmentSubmission;
use RuntimeException;

class AccountDeletionService
{
    use MailSenderTrait;

    public function sendConfirmation(User $user): void
    {
        if (! self::setMailConfig()) {
            throw new RuntimeException('Account deletion confirmation email could not be configured.');
        }

        $emailHash = hash_hmac('sha256', $user->email, (string) config('app.key'));
        $url = URL::temporarySignedRoute(
            'account-deletion.confirm.show',
            now()->addHour(),
            ['user' => $user->id, 'email_hash' => $emailHash]
        );

        Mail::raw(
            "We received a request to delete your RevisionHub account and associated data.\n\n"
                ."To confirm and complete the request within 60 minutes, visit:\n{$url}\n\n"
                .'If you did not request this, you can ignore this email.',
            function ($message) use ($user): void {
                $message->to($user->email)->subject('Confirm your RevisionHub account deletion');
            }
        );
    }

    public function delete(User $user, AiDocumentService $aiDocumentService): void
    {
        $this->deleteProfileFiles($user);

        if ($user->role === 'student') {
            $this->deleteStudentSubmissionFiles($user);
        }

        if ($user->role === 'instructor') {
            $this->deleteInstructorAiDocuments($user, $aiDocumentService);
        }

        DB::transaction(function () use ($user): void {
            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }
            if (Schema::hasTable('user_devices')) {
                $user->devices()->delete();
            }
            if (Schema::hasTable('device_sessions')) {
                $user->deviceSessions()->delete();
            }
            if (Schema::hasTable('notifications')) {
                $user->notifications()->delete();
            }

            $this->deleteRowsOwnedBy($user->id);
            $this->anonymizeOrders($user);

            if ($user->role === 'instructor') {
                $this->anonymizeInstructor($user);

                return;
            }

            $user->delete();
        });
    }

    private function deleteProfileFiles(User $user): void
    {
        foreach ([$user->image, $user->cover] as $path) {
            if (! is_string($path) || $path === '' || Str::contains($path, 'uploads/website-images')) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', $path), '/');
            if (! Str::startsWith($relativePath, 'uploads/') || Str::contains($relativePath, ['../', '..\\'])) {
                continue;
            }

            $absolutePath = public_path($relativePath);
            if (File::exists($absolutePath) && ! File::delete($absolutePath)) {
                throw new RuntimeException('An account profile file could not be deleted.');
            }
        }
    }

    private function deleteInstructorAiDocuments(User $user, AiDocumentService $aiDocumentService): void
    {
        if (! Schema::hasTable('ai_documents')) {
            return;
        }

        AiDocument::withTrashed()
            ->where('instructor_id', $user->id)
            ->get()
            ->each(function (AiDocument $document) use ($aiDocumentService): void {
                $aiDocumentService->deleteDocument($document);
                $document->forceDelete();
            });
    }

    private function deleteStudentSubmissionFiles(User $user): void
    {
        if (! Schema::hasTable('assignment_submissions')) {
            return;
        }

        AssignmentSubmission::where('student_id', $user->id)
            ->get()
            ->each(function (AssignmentSubmission $submission): void {
                foreach ($submission->file_paths ?? [] as $path) {
                    if (! is_string($path) || ! Str::startsWith($path, 'assignments/') || Str::contains($path, ['../', '..\\'])) {
                        continue;
                    }

                    if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                        throw new RuntimeException('An account assignment file could not be deleted.');
                    }
                }
            });
    }

    private function deleteRowsOwnedBy(int $userId): void
    {
        foreach (Schema::getTableListing() as $table) {
            if ($table === 'users') {
                continue;
            }

            if (Schema::hasColumn($table, 'user_id')) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }

        foreach (['assignment_submissions' => 'student_id'] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $userId)->delete();
            }
        }

        foreach ([
            'instructor_earnings_holds' => 'instructor_id',
            'zoom_credentials' => 'instructor_id',
            'jitsi_settings' => 'instructor_id',
            'course_partner_instructors' => 'instructor_id',
        ] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $userId)->delete();
            }
        }
    }

    private function anonymizeOrders(User $user): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'buyer_id')) {
            $buyerUpdates = ['buyer_id' => null];
            foreach (['payment_details', 'order_details'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $buyerUpdates[$column] = null;
                }
            }
            DB::table('orders')->where('buyer_id', $user->id)->update($buyerUpdates);
        }

        if ($user->role === 'instructor' && Schema::hasColumn('orders', 'seller_id')) {
            DB::table('orders')->where('seller_id', $user->id)->update(['seller_id' => null]);
        }
    }

    private function anonymizeInstructor(User $user): void
    {
        $anonymizedData = [
            'name' => 'Deleted instructor',
            'email' => "deleted-instructor-{$user->id}@deleted.invalid",
            'email_verified_at' => null,
            'password' => Str::random(64),
            'remember_token' => null,
            'google_id' => null,
            'phone' => null,
            'image' => '/uploads/website-images/frontend-avatar.png',
            'cover' => '/uploads/website-images/frontend-cover.png',
            'bio' => null,
            'short_bio' => null,
            'job_title' => null,
            'gender' => null,
            'age' => null,
            'country_id' => null,
            'state' => null,
            'city' => null,
            'address' => null,
            'facebook' => null,
            'twitter' => null,
            'linkedin' => null,
            'website' => null,
            'github' => null,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'otp_code' => null,
            'otp_purpose' => null,
            'otp_expires_at' => null,
            'forget_password_token' => null,
            'subscription_plan_id' => null,
            'subscription_started_at' => null,
            'subscription_expires_at' => null,
            'status' => 'deactive',
            'is_banned' => 'yes',
        ];

        $anonymizedData = array_filter(
            $anonymizedData,
            static fn (string $column): bool => Schema::hasColumn('users', $column),
            ARRAY_FILTER_USE_KEY
        );

        $user->forceFill($anonymizedData)->save();
    }
}
