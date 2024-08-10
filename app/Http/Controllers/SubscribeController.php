<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    public function addSubscription(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'competition_id' => 'required|exists:competitions,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'competition_type' => 'required|string',
        ]);

        $subscription = Subscription::create([
            'user_id' => $request->user_id,
            'competition_id' => $request->competition_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'competition_type' => $request->competition_type,
        ]);
        $competition = Competition::find($request->competition_id);
        $competition->increment('subscriber_by_one');
        return response()->json(['message' => 'Subscription added successfully', 'subscription' => $subscription], 200);
    }
 
}
