<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;


class TimezoneController extends Controller
{
    public function convertTimestamps(Request $request)
    {
        $timezone = $request->input('timezone');

        if (!$timezone) {
            return response()->json(['error' => 'Timezone is required'], 400);
        }

        Artisan::call('convert:created_at', [
            'timezone' => $timezone,
        ]);

        return response()->json(['success' => true, 'message' => 'Timestamps converted to ' . $timezone . ' timezone']);
    }
}
