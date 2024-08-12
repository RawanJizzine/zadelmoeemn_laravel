<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait HandlesTimezone
{
    public static function bootHandlesTimezone()
    {
        static::creating(function ($model) {
            // Retrieve the timezone from the request
            $timezone = request()->input('timezone', 'UTC');

            // Convert the current time to UTC based on the provided timezone
            $model->created_at = Carbon::now($timezone)->setTimezone('UTC');
        });
    }
}
