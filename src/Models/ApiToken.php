<?php

namespace Landman\MultiTokenAuth\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Landman\MultiTokenAuth\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Class ApiToken
 * @package App
 */
class ApiToken extends Model
{
    use HasUuidKey;

    /** @var bool */
    public static $snakeAttributes = false;

    /** @var string */
    protected $keyType = 'string';

    /** @var array */
    protected $guarded = ['id'];

    /** @var array */
    protected $encryptable = [];

    /**  */
    const TOKEN_LENGTH = 60;

    /** @var array */
    protected $casts = [
        'expires_at' => 'datetime',
        'remember' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeInvalid($query)
    {
        return $query->withTrashed()
            ->where(function ($query) {
                return $query->where('remember', false)
                    ->where('updated_at', '<=', now()->addMonth());
            })->orWhere(function ($query) {
                return $query->where('remember', true)
                    ->where('expires_at', '<=', now());
            })->orWhereNull('deleted_at');
    }


    /**
     *
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function (ApiToken $token) {
            $token->setFreshFields();
        });
    }


    /**
     * @return $this
     */
    public function refresh()
    {
        $this->restore();
        $this->setFreshFields()->save();
        return $this;
    }

    /**
     * @return $this
     */
    private function setFreshFields()
    {
        $this->token = $this->generateToken();
        $this->refresh_token = $this->generateToken();
        $this->expires_at = $this->should_forget ? $this->generateExpiresAtDate() : null;
        return $this;
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        return str_random(static::TOKEN_LENGTH);
    }

    /**
     * @return string
     */
    public static function generateNewToken()
    {
        return (new self)->generateToken();
    }

    /**
     * @return \Carbon\Carbon
     */
    public function generateExpiresAtDate()
    {
        return now()->addMinutes(config('session.lifetime'));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value !== null) {
            if (in_array($key, $this->encryptable)) {
                $value = $this->decryptValue($value);
            }
        }
        return $value;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->encryptable)) {
            $value = $this->encryptValue($value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @param $value
     * @return string
     */
    public function encryptValue($value): string
    {
        return Crypt::encrypt($value);
    }

    /**
     * @param $field
     * @param $value
     * @return bool
     */
    public function equalsEncryptedAttribute($field, $value)
    {
        return $this->getAttribute($field) === $value;
    }

    /**
     * @param $value
     * @return string
     */
    public function decryptValue($value): string
    {
        return Crypt::decrypt($value);
    }

    /**
     * @return bool
     */
    public function getShouldForgetAttribute(): bool
    {
        return !$this->should_remember;
    }

    /**
     * @return bool
     */
    public function getShouldRememberAttribute(): bool
    {
        return $this->remember === true && !config('auth.api_tokens_expire', false);
    }

    /**
     * @return \Carbon\Carbon
     */
    public function getRememberExpiryTime(): Carbon
    {
        return $this->updated_at->copy()->addMonth();
    }

    /**
     * @return bool
     */
    public function shouldBeInvalidated(): bool
    {
        return now()->gte($this->remember ? $this->getRememberExpiryTime() : $this->expires_at);
    }

    /**
     * @return bool
     */
    public function invalidate(): bool
    {
        return $this->forceDelete();
    }

    /**
     * @return bool
     */
    public function expire(): bool
    {
        return $this->delete();
    }


    /**
     * @return mixed|string
     */
    public function getTable()
    {
        parent::getTable();
        return Config::get('multipletokens.tables.api_tokens');
    }
}
