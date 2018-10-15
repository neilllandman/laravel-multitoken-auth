<?php

namespace Landman\MultiTokenAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Landman\MultiTokenAuth\Classes\TokenApp;
use Landman\MultiTokenAuth\Traits\HasUuidKey;

/**
 * Class ApiToken
 * @package App
 *
 * @property string $id
 * @property string $name
 * @property string $value
 * @property Carbon|string $created_at
 * @property Carbon|string $updated_at
 */
class ApiClient extends Model
{
    use HasUuidKey;

    /** @var bool */
    public static $snakeAttributes = false;


    /** @var array */
    protected $guarded = ['id'];


    /**
     * @param $name
     * @return mixed
     */
    public static function make($name)
    {
        return self::create([
            'name' => $name,
            'value' => self::newId(),
        ]);
    }

    /**
     * @return $this
     */
    public function remake()
    {
        $this->update(['value' => self::newId()]);
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getTable()
    {
        parent::getTable();
        return TokenApp::config('table_clients');
    }
}
