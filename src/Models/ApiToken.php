<?php

namespace Landman\MultiTokenAuth\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use Landman\MultiTokenAuth\Classes\TokenApp;
use Landman\MultiTokenAuth\Traits\HasUuidKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Class ApiToken
 * @package App
 *
 * @property string $id
 * @property string $token
 * @property string|null $refresh_token
 * @property bool $remember
 * @property string $user_agent
 * @property string $device
 *
 * @property string $user_id
 * @property Carbon|string $expires_at
 * @property Carbon|string $deleted_at
 * @property Carbon|string $created_at
 * @property Carbon|string $updated_at
 */
class ApiToken extends Model
{
    use HasUuidKey;
    use SoftDeletes;

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
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'expires_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(Config::get('multipletokens.model'));
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeInvalid($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * @return mixed
     */
    public static function cleanInvalid()
    {
        return ApiToken::invalid()->delete();
    }

    /**
     * @return bool
     */
    public static function shouldExpire(): bool
    {
        return Config::get('multipletokens.token_lifetime') > 0;
    }


    /**
     *
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function (ApiToken $token) {
            $token->token = static::generateNewToken();
            $token->refresh_token = static::generateNewToken();
            $token->expires_at = self::generateExpiresAtDate();
            return true;
        });
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
    public static function generateExpiresAtDate()
    {
        return now()->addMinutes(Config::get('multipletokens.token_lifetime', 43200));
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
    public function shouldBeInvalidated(): bool
    {
        return TokenApp::config('token_lifetime') > 0 && $this->hasExpired();
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->expires_at === null || now()->gte($this->expires_at);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function invalidate(): bool
    {
        return $this->delete();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function expire(): bool
    {
        return $this->invalidate();
    }

    /**
     * @return ApiToken
     */
    public function updateExpiresAt(): ApiToken
    {
        $this->update(['expires_at' => self::generateExpiresAtDate()]);
        return $this;
    }

    /**
     * @return ApiToken
     */
    public function setExpiresAt(): ApiToken
    {
        $this->expires_at = self::generateExpiresAtDate();
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getTable()
    {
        parent::getTable();
        return TokenApp::config('table_tokens');
    }

    /**
     * @return array
     */
    public function toApiFormat(){
//        $expires_at = $this->expires_at instanceof Carbon ? $this->expires_at->toDateTimeString() : $this->expires_at;
        return [
//            'user_id' => $this->user_id,
            'token' => $this->token,
//            'expires_at' => ApiToken::shouldExpire() ? new Carbon($this->expires_at) : null,
        ];
    }
}
