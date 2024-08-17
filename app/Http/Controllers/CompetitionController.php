<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionCounter;
use App\Models\CompetitionCounterSalawat;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use NunoMaduro\Collision\Adapters\Phpunit\Subscribers\Subscriber;

class CompetitionController extends Controller
{
    public function store(Request $request)
{
   
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'type' => 'required|string|max:255',
        'instagram' => 'required|string|max:255',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'competition_number' => 'required|string|max:255',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image
    ]);

    // Handle the image upload
    if ($request->hasFile('image')) {
      
      $imageName = time() . '.' . $request->image->extension();
    
        // Move the image to the public directory
        $request->image->move(public_path('imageapp'), $imageName);

        // Save the image name to the user record
        $validated['image'] = $imageName;

  }

    $competition = Competition::create($validated);

    return response()->json([
        'message' => 'Competition created successfully!',
        'competition' => $competition
    ], 201);
}
public function getRecentCompetitions()
{


    $user = Auth::user();

    // Retrieve the user's timezone, default to 'UTC' if not set
    $timezone = $user->timezone ?? 'UTC';

    // Calculate the date limit in the user's timezone
    $dateLimit = Carbon::now($timezone)->subDays(50);

    // Convert the date limit to UTC for comparison with the created_at field
    $dateLimitUtc = $dateLimit->setTimezone('UTC');

    // Get competitions created within the date limit and with subscriber_by_one < competition_number_total
    $competitions = Competition::where('created_at', '>=', $dateLimitUtc)
                                ->whereColumn('subscriber_by_one', '<', 'competition_number')
                                ->get();

    return response()->json($competitions);
}

public function getCompetitionDetails($id)
{
    // Find the competition by ID
    $competition = Competition::find($id);

    if (!$competition) {
        return response()->json(['error' => 'Competition not found'], 404);
    }

    // Get the authenticated user
    $user = Auth::user();

    // Retrieve the user's timezone, default to 'UTC' if not set
    $timezone = $user->timezone ?? 'UTC';

    // Convert the start and end dates to the user's timezone
    $startDate = Carbon::parse($competition->start_date)->setTimezone($timezone);
    $endDate = Carbon::parse($competition->end_date)->setTimezone($timezone);

    return response()->json([
        'id' => $competition->id,
        'name' => $competition->name,
        'start_date' => $startDate->toDateTimeString(), // Format as a string for JSON response
        'end_date' => $endDate->toDateTimeString(),     // Format as a string for JSON response
    ]);
}

public function getUserCompetitions($userId)
{
    // Fetch subscriptions for the user, ordered by created_at in descending order
    $subscriptions = Subscription::where('user_id', $userId)
                                  ->orderBy('created_at', 'desc')
                                  ->get();
    
    // Check if there are any subscriptions
    if ($subscriptions->isEmpty()) {
        return response()->json(['message' => 'No subscriptions found for this user.'], 404);
    }
    
    // Get the authenticated user to retrieve the timezone
    $user = Auth::user();
    $timezone = $user->timezone ?? 'UTC'; // Default to 'UTC' if timezone is not set

    // Get the current date in the user's timezone
    $today = Carbon::now($timezone)->startOfDay();
    $todayFlutter = Carbon::now($timezone)->startOfDay()->setTimezone($timezone)->format('Y-m-d\TH:i:sP');

    // Initialize the array to hold competition data
    $competitions = [];
    
    // Define status priorities
    $statusPriority = [
        'new' => 1,
        'active' => 2,
        'old' => 3
    ];
    
    // Iterate over subscriptions to fetch related competitions
    foreach ($subscriptions as $subscription) {
        $competition = Competition::find($subscription->competition_id);

        // Only add competition to the result if it exists
        if ($competition) {
            // Convert competition dates to user's timezone
            $startDate = Carbon::parse($competition->start_date)->setTimezone($timezone)->startOfDay();
            $endDate = Carbon::parse($competition->end_date)->setTimezone($timezone)->startOfDay();

            // Check if the type is "صلوات" and adjust the start date by subtracting 8 hours
            $startDateAdjusted = $startDate->copy()->subHours(8);

            // Determine the competition status based on the user's current date
            if ($today->lt($startDate)) {
                $competition->status = 'new';
            } elseif ($today->gte($startDate) && $today->lte($endDate) || 
                    ($competition->type == "صلوات" && $today->gte($startDateAdjusted) && $today->lte($endDate))) {
                $competition->status = 'active';
            } elseif ($today->gt($endDate)) {
                $competition->status = 'old';
            }

            // Save the updated competition status
            $competition->save();

            // Add the competition to the result array
            $competitions[] = [
                'subscription_id' => $subscription->id,
                'competition' => $competition,
                'today' => $todayFlutter
            ];
        }
    }
    
    // Sort the competitions based on status priority
    usort($competitions, function($a, $b) use ($statusPriority) {
        return $statusPriority[$a['competition']->status] <=> $statusPriority[$b['competition']->status];
    });
    
    // Return the competitions sorted by the status priority
    return response()->json($competitions);
}

// Adjusted code for time zone handling

public function getResultsestkhfar($competitionId, Request $request)
{
    $stage = $request->query('stage');
    $user = Auth::user(); // Get the authenticated user
    $userId = $user->id; // Get the user's ID
    $timezone = $user->timezone; // Get the user's timezone

    // Get the competition details including start and end dates
    $competition = Competition::find($competitionId);

    if (!$competition) {
        return response()->json(['error' => 'Competition not found'], 404);
    }

    if (!$timezone) {
        return response()->json(['error' => 'User timezone not set'], 400);
    }

    // Parse dates and convert to user's timezone
    $startDate = Carbon::parse($competition->start_date)->timezone($timezone);
    $endDate = Carbon::parse($competition->end_date)->timezone($timezone);

    // Get the current date in the user's timezone
    $currentDate = Carbon::now($timezone);

    // Initialize the query
    $query = CompetitionCounter::where('competition_id', $competitionId);

    // Calculate the endDate + 1 day
    $endDatePlusOneDay = $endDate->copy()->addDay();
    
    // Filter based on the stage
    if ($stage == 'النصف النهائي') {
        // Calculate the total number of days
        $totalDays = $startDate->diffInDays($endDate) + 1; // Including both start and end dates
        
        // Determine the midpoint based on whether the total number of days is even or odd
        $halfDays = (int) floor($totalDays / 2);
        $halfDate = $startDate->copy()->addDays($halfDays - 1);
        
        // Adjust halfDate to ensure proper splitting
        if ($totalDays % 2 == 1) {
            // If total days is odd, include the middle day in the first half
            $halfDate = $startDate->copy()->addDays($halfDays);
        }

        // Use user's timezone for date formatting
        $query->whereBetween('date', [
            $startDate->format('Y-m-d'),
            $halfDate->format('Y-m-d')
        ]);
        
    } elseif($stage == 'النهائي' && $currentDate->toDateString() >= $endDatePlusOneDay->toDateString()) {
        $query->whereBetween('date', [
            $startDate->startOfDay()->format('Y-m-d H:i:s'),
            $endDate->endOfDay()->format('Y-m-d H:i:s')
        ]);
    } else {
        // Set the query to return an empty result
        $query->whereRaw('1 = 0'); // This creates a condition that is always false
    }

    // Get the results and calculate the sum of counter_value for each subscription_id
    $results = $query->select('subscription_id', DB::raw('SUM(counter_value) as total_counter_value'))
                     ->groupBy('subscription_id')
                     ->orderBy('total_counter_value', 'desc')
                     ->take(100)
                     ->get();

    // Enhance the results with user details
    $finalResults = [];
    foreach ($results as $result) {
        $subscription = DB::table('subscriptions')->find($result->subscription_id);
        if ($subscription) {
            $user = DB::table('users')->find($subscription->user_id);
            if ($user) {
                $finalResults[] = [
                    'full_name' => $user->username,
                    'image' => $user->image,
                    'total_counter_value' => $result->total_counter_value,
                ];
            }
        }
    }

    // Return the final results as a JSON response
    return response()->json($finalResults);
}




public function getnataeejsalawat($competition_id)
{
    $competition = Competition::findOrFail($competition_id);
    
    // Get the authenticated user and their timezone
    $user = Auth::user();
    $userTimezone = $user->timezone;

    // Get the current date and time in the user's timezone
    $currentDateTime = Carbon::now($userTimezone);

    // Ensure end_date is cast to a Carbon instance and convert it to the user's timezone
    $competitionEndDate = Carbon::parse($competition->end_date, 'UTC')->setTimezone($userTimezone);

    // Check if the end date plus one day has passed
    if ($currentDateTime->greaterThan($competitionEndDate->addDay())) {
        $result = $this->fetchCompetitionCounters($competition_id, $userTimezone);
        return response()->json($result);
    }

    return response()->json(['message' => 'Competition is still ongoing.'], 403);
}

private function fetchCompetitionCounters($competition_id, $userTimezone)
{
    $counters = CompetitionCounterSalawat::with(['subscription.user' => function($query) use ($userTimezone) {
            $query->select('id', 'username', 'image')
                  ->addSelect(DB::raw("CONVERT_TZ(created_at, '+00:00', '$userTimezone') as created_at_user_timezone"));
        }])
        ->where('competition_id', $competition_id)
        ->get()
        ->groupBy('subscription_id')
        ->map(function ($group) {
            $totalCounterValue = $group->sum('counter_value');
            $firstEntry = $group->first();
            $user = $firstEntry->subscription->user;

            return [
                'full_name' => $user->username,
                'image' => $user->image,
                'total_counter_value' => $totalCounterValue,
                'created_at_user_timezone' => $user->created_at_user_timezone
            ];
        })
        ->values();

    // Sort the results by total_counter_value from largest to smallest
    $sortedCounters = $counters->sortByDesc('total_counter_value')->take(50)->values()->toArray();

    return $sortedCounters;
}
}
