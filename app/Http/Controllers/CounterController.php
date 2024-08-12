<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionCounter;
use App\Models\CompetitionCounterSalawat;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CounterController extends Controller
{
    public function saveEstkhfar(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $userTimezone = $user->timezone; // Assuming the user's timezone is stored in the `timezone` field
    
        // Get current date and time in user's timezone
        $today = Carbon::now($userTimezone);
        
        $date = $today->format('Y-m-d');
    
        $validatedData = $request->validate([
            'competition_id' => 'required|string|max:255',
            'user_id' => 'required|string|max:255',
            'counter_value' => 'required|string|max:255',
        ]);
    
        // Retrieve subscription and competition
        $subscription = Subscription::where('competition_id', $validatedData['competition_id'])
            ->where('user_id', $validatedData['user_id'])
            ->first();
    
        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }
    
        $subscriptionId = $subscription->id;
    
        $competition = Competition::find($validatedData['competition_id']);
    
        if (!$competition) {
            return response()->json(['error' => 'Competition not found'], 404);
        }
    
        // Convert competition dates to user's timezone
        $competitionStartDate = Carbon::parse($competition->start_date, 'UTC')->setTimezone($userTimezone);
        $competitionEndDate = Carbon::parse($competition->end_date, 'UTC')->setTimezone($userTimezone);
    
        // Check if today is within competition dates and not a Friday
        if ($date == $competitionStartDate->format('Y-m-d') || 
            $date == $competitionEndDate->format('Y-m-d') || 
            $today->between($competitionStartDate, $competitionEndDate) && !$today->isFriday()) {
    
            $counter = CompetitionCounter::updateOrCreate(
                [
                    'competition_id' => $validatedData['competition_id'],
                    'user_id' => $validatedData['user_id'],
                    'subscription_id' => $subscriptionId,
                    'date' => $date,
                ],
                [
                    'counter_value' => $validatedData['counter_value'],
                ]
            );
    
            return response()->json([
                'counter' => $counter
            ], 200);
        } else {
            return response()->json([
                'message' => 'Today is either not within the competition dates or it is a Friday',
            ], 400);
        }
    }
    
    public function getCounterValue(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $userTimezone = $user->timezone; // Assuming the user's timezone is stored in the `timezone` field
    
        $competitionId = $request->query('competition_id');
        $userId = $request->query('user_id');
    
        // Perform validation as needed
        if (!$competitionId || !$userId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }
    
        // Get today's date in the user's timezone
        $today = Carbon::now($userTimezone)->format('Y-m-d');
    
        // Fetch the counter value for today
        $counterValue = CompetitionCounter::where('competition_id', $competitionId)
            ->where('user_id', $userId)
            ->whereDate('date', $today) // Filter by today's date in user's timezone
            ->value('counter_value');  // Replace 'counter_value' with the actual column name
   
        if ($counterValue === null) {
            return response()->json(['counter_value' => 0], 200); // Default to 0 if not found
        }
    
        return response()->json(['counter_value' => $counterValue], 200);
    }
    
    public function getNatijatiestkhfar(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user
        $userTimezone = $user->timezone; // Assuming the user's timezone is stored in the `timezone` field
    
        $competitionId = $request->query('competition_id');
        $userId = $request->query('user_id');
    
        if (!$competitionId || !$userId) {
            return response()->json(['error' => 'competition_id and user_id are required'], 400);
        }
    
        // Fetch the competition details
        $competition = Competition::find($competitionId);
    
        if (!$competition) {
            return response()->json(['error' => 'Competition not found'], 404);
        }
    
        // Convert competition start and end dates to the user's timezone
        $startDate = Carbon::parse($competition->start_date)->setTimezone($userTimezone)->format('Y-m-d');
        $endDate = Carbon::parse($competition->end_date)->setTimezone($userTimezone)->format('Y-m-d');
    
        // Fetch the CompetitionCounter records within the date range
        $competitionCounters = CompetitionCounter::where('user_id', $userId)
            ->where('competition_id', $competitionId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
    
        return response()->json($competitionCounters);
    }
    
    public function savesalawat(Request $request)
{
    // Validate incoming request
    $validatedData = $request->validate([
        'competition_id' => 'required|string|max:255',
        'user_id' => 'required|string|max:255',
        'counter_value' => 'required|string|max:255',
    ]);

    // Retrieve the authenticated user and their timezone
    $user = Auth::user();
    $userTimezone = $user->timezone;

    // Fetch the subscription details
    $subscription = Subscription::where('competition_id', $validatedData['competition_id'])
        ->where('user_id', $validatedData['user_id'])
        ->first();

    if (!$subscription) {
        return response()->json(['error' => 'Subscription not found'], 404);
    }
    $subscriptionId = $subscription->id;

    // Fetch the competition details
    $competition = Competition::where('id', $validatedData['competition_id'])->first();

    if (!$competition) {
        return response()->json(['error' => 'Competition not found'], 404);
    }

    // Convert dates to the user's timezone
    $startDate = Carbon::parse($competition->start_date)->setTimezone($userTimezone)->format('Y-m-d');
    $endDate = Carbon::parse($competition->end_date)->setTimezone($userTimezone)->format('Y-m-d');
    $today = Carbon::now()->setTimezone($userTimezone)->format('Y-m-d');

    // Check if the current date is within the competition dates and if today is a Friday
    if ($today == $startDate || $today == $endDate || ($today >= $startDate && $today <= $endDate && Carbon::parse($today)->isFriday())) {
        $counter = CompetitionCounterSalawat::updateOrCreate(
            [
                'competition_id' => $validatedData['competition_id'],
                'user_id' => $validatedData['user_id'],
                'subscription_id' => $subscriptionId,
                'date' => $today,
            ],
            [
                'counter_value' => $validatedData['counter_value'],
            ]
        );

        return response()->json(['counter' => $counter], 200);
    } else {
        return response()->json([
            'message' => 'Today is either not within the competition dates or it is not a Friday',
        ], 400);
    }
}

public function getCounterSalawat(Request $request)
{
    $competitionId = $request->query('competition_id');
    $userId = $request->query('user_id');

    // Perform validation as needed
    if (!$competitionId || !$userId) {
        return response()->json(['error' => 'Invalid parameters'], 400);
    }

    // Retrieve the authenticated user and their timezone
    $user = Auth::user();
    $userTimezone = $user->timezone;

    // Fetch the competition details
    $competition = Competition::find($competitionId);
    if (!$competition) {
        return response()->json(['error' => 'Competition not found'], 404);
    }

    // Convert today's date to the user's timezone
    $today = Carbon::now()->setTimezone($userTimezone)->format('Y-m-d');

    // Fetch the counter value for today in the user's timezone
    $counterValue = CompetitionCounterSalawat::where('competition_id', $competitionId)
        ->where('user_id', $userId)
        ->whereDate('date', $today) // Filter by today's date in the user's timezone
        ->value('counter_value');

    if ($counterValue === null) {
        return response()->json(['counter_value' => 0], 200); // Default to 0 if not found
    }

    return response()->json(['counter_value' => $counterValue], 200);
}

}
