<?php

namespace Landman\MultiTokenAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Landman\MultiTokenAuth\Traits\HasUuidKey;

/**
 * Class ApiToken
 * @package App
 */
class ClientId extends Model
{

    use SoftDeletes;
    use HasUuidKey;

    /** @var bool */
    public static $snakeAttributes = false;


    /** @var array */
    protected $guarded = ['id'];


    /**
     * @param $name
     * @return mixed
     */
    public static function make($name){
        return self::create([
           'name' => $name,
           'value' => self::newId(),
        ]);
    }
}
