<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\AiCreditLedger;
use App\Models\JitsiSetting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;
use Modules\Assignment\app\Models\Assignment;
use Modules\Assignment\app\Models\AssignmentSubmission;
use Modules\InstructorRequest\app\Models\InstructorRequest;
use Modules\Location\app\Models\Country;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Refund\app\Models\InstructorEarningsHold;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'role',
        'name',
        'email',
        'google_id',
        'country_id',
        'phone',
        'password',
        'status',
        'is_banned',
        'otp_code',
        'otp_purpose',
        'otp_expires_at',
        'forget_password_token',
        'email_verified_at',
        'google_access_token',
        'google_refresh_token',
        'subscription_plan_id',
        'subscription_started_at',
        'subscription_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function favoriteCourses()
    {
        return $this->belongsToMany(Course::class, 'favorite_course_user')->withTimestamps();
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
    public function scopeStudent($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeActive($query)
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', UserStatus::DEACTIVE);
    }

    public function scopeBanned($query)
    {
        return $query->where('is_banned', UserStatus::BANNED);
    }

    public function scopeUnbanned($query)
    {
        return $query->where('is_banned', UserStatus::UNBANNED);
    }

    public function hasActiveSubscription(): bool
    {
        return hasActiveSubscription($this);
    }

    public function scopeInstructor($query)
    {
        return $query->where('role', 'instructor');
    }

    public function socialite()
    {
        return $this->hasMany(SocialiteCredential::class, 'user_id');
    }

    function instructorInfo(): HasOne
    {
        return $this->hasOne(InstructorRequest::class, 'user_id', 'id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'instructor_id');
    }
    function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'user_id', 'id');
    }

    function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id', 'id');
    }
    function zoom_credential(): HasOne
    {
        return $this->hasOne(ZoomCredential::class, 'instructor_id', 'id');
    }
    function jitsi_credential(): HasOne
    {
        return $this->hasOne(JitsiSetting::class, 'instructor_id', 'id');
    }
    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_id', 'id')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where(fn($query) => $query->where('item_type', 'course')->orWhereNull('item_type'))
                        ->whereHas('course', function ($query) {
                            $query->where(['is_approved' => 'approved', 'status' => 'active']);
                        });
                })->orWhere(function ($query) {
                    $query->where('item_type', 'product')
                        ->whereHas('product', function ($query) {
                            $query->where(['is_approved' => 'approved', 'status' => 'active']);
                        });
                });
            });
    }
    public function instructorEarningsHolds()
    {
        return $this->hasMany(InstructorEarningsHold::class, 'instructor_id', 'id');
    }

    // Accessor for cart count
    public function getCartCountAttribute()
    {
        if (!Schema::hasTable('carts') || !Schema::hasTable('courses') || !Schema::hasTable('products')) {
            return 0;
        }

        try {
            return $this->carts()->sum('qty');
        } catch (QueryException) {
            return 0;
        }
    }
    public function getCartTotalAttribute()
    {
        if (!Schema::hasTable('carts') || !Schema::hasTable('courses') || !Schema::hasTable('products')) {
            return 0;
        }

        try {
            return $this->carts()
                ->leftJoin('courses', 'courses.id', '=', 'carts.course_id')
                ->leftJoin('products', 'products.id', '=', 'carts.product_id')
                ->selectRaw("
                    SUM(
                        carts.qty * CASE
                            WHEN carts.item_type = 'product' THEN IFNULL(NULLIF(products.discount, 0), products.price)
                            ELSE IFNULL(NULLIF(courses.discount, 0), courses.price)
                        END
                    ) as total
                ")
                ->value('total') ?? 0;
        } catch (QueryException) {
            return 0;
        }
    }
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'instructor_id');
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function aiCreditLedgers(): HasMany
    {
        return $this->hasMany(AiCreditLedger::class, 'user_id', 'id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            // Delete related instructor request
            $user->instructorInfo()->delete();
        });
    }
}
