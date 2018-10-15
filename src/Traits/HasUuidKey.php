<?php

namespace Landman\MultiTokenAuth\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Trait HasUuidKey
 * @package Landman\MultiTokenAuth\Traits
 */
trait HasUuidKey
{
    /**
     * Boot the trait
     */
    public static function bootHasUUidKey()
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = $model->generateNewId();
        });
    }

    /**
     * Get a new version 4 (random) UUID with a timestamp prefix.
     * The UUID is timestamped for incrementing order.
     *
     * @return string
     * @throws \Exception
     */
    public function generateNewId(): string
    {
        $uuid = (string)Uuid::uuid4();
        $microtime = str_pad(str_replace([' ', '.'], '', microtime(true)), 16, 0);
        $hex = dechex($microtime);

        $hexLength = strlen($hex);
        $l1 = strpos($uuid, "-");
        $uuid1 = substr($uuid, $l1 + 1);
        $l2 = strpos($uuid1, "-");
        $uuid2 = substr($uuid1, $l2 + 1);
        $l3 = strpos($uuid2, "-");
        $uuid3 = substr($uuid2, $l3 + 1);
        $a = [
            substr($hex, 0, $l1),
            str_pad(substr($hex, $l1, $l2), $l2, "f"),
        ];
        if ($hexLength > strlen(implode("", $a))) {
            $a[] = str_pad(substr($hex, $l1 + $l2, $l3), $l3, "0");
        }
        $prefix = implode("-", $a);
        $uuid = substr($uuid, strlen($prefix));
        return "{$prefix}{$uuid}";
    }

    /**
     * @return string
     */
    public static function newId(): string
    {
        return (new static)->generateNewId();
    }

    /**
     *
     * {@inheritdoc}
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     * {@inheritdoc}
     *
     * @return string
     */
    public function getKeyType()
    {
        return "string";
    }
}
