<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionCounter;
use App\Models\CompetitionCounterSalawat;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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


    $dateLimit = Carbon::now()->subDays(20000);

        // Get competitions created in the last 50 days with subscriber_by_one < competition_number_total
        $competitions = Competition::where('created_at', '>=', $dateLimit)
                                    ->whereColumn('subscriber_by_one', '<', 'competition_number')
                                    ->get();

        return response()->json($competitions);
}

public function getCompetitionDetails($id)
    {
        $competition = Competition::find($id);

        if (!$competition) {
            return response()->json(['error' => 'Competition not found'], 404);
        }

        return response()->json([
            'id' => $competition->id,
            'name' => $competition->name,
            'start_date' => $competition->start_date,
            'end_date' => $competition->end_date,
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
        
        // Get the current date
        $date = Carbon::now()->format('Y-m-d');
    
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
            $today = Carbon::today();
            // Only add competition to the result if it exists and meets the date condition
            if ($competition) {
                $endDate = Carbon::parse($competition->end_date);
                $startDate = Carbon::parse($competition->start_date);
                if ($today->lt($startDate)) {
                    $competition->status = 'new';
                } elseif ($today->between($startDate, $endDate) || $date == $competition->start_date || $date == $competition->end_date) {
                    $competition->status = 'active';
                } elseif ($today->gt($endDate)) {
                    
                    $competition->status = 'old';
                }
        
                $competition->save();
                // Check if the current date is equal to or less than end_date + 7 days
                $competitions[] = [
                    'subscription_id' => $subscription->id,
                    'competition' => $competition
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
    
    public function getResultsestkhfar($competitionId, Request $request)
{
    $stage = $request->query('stage');

    // Get the competition details including start and end dates
    $competition = Competition::where('id', $competitionId)->first();

    if (!$competition) {
        return response()->json(['error' => 'Competition not found'], 404);
    }

    $startDate = $competition->start_date;
    $endDate = $competition->end_date;

    // Initialize the query
    $query = CompetitionCounter::where('competition_id', $competitionId);
    $currentDate = Carbon::now()->format('Y-m-d');

    // Calculate endDate + 1 day
    $endDatePlusOneDay = Carbon::parse($endDate)->addDay()->format('Y-m-d');
    // Filter based on the stage and calculate the sum of counter_value for each subscription_id
    if ($stage == 'النصف النهائي') {
        $halfDate = date('Y-m-d', strtotime($startDate . ' + ' . ceil((strtotime($endDate) - strtotime($startDate)) / 2 / 86400) . ' days'));
        $query->whereBetween('date', [$startDate, $halfDate]);
    } elseif($stage == 'النهائي' && $currentDate == $endDatePlusOneDay) {
        // Ensure the end date is inclusive by using a date range that includes the end date.
        $query->whereBetween('date', [date('Y-m-d', strtotime($startDate . ' 00:00:00')), date('Y-m-d', strtotime($endDate . ' 23:59:59'))]);
    } else {
        // Set the query to return an empty result
        $query->whereRaw('1 = 0'); // This creates a condition that is always false
    }


    // Get the results and calculate the sum of counter_value for each subscription_id
   $results = $query->select('subscription_id', DB::raw('SUM(counter_value) as total_counter_value'))
                     ->groupBy('subscription_id')
                     ->orderBy('total_counter_value', 'desc')
                     ->get();

    // Enhance the results with user details
    $finalResults = [];
    foreach ($results as $result) {
        $subscription = DB::table('subscriptions')->where('id', $result->subscription_id)->first();
        if ($subscription) {
            $user = DB::table('users')->where('id', $subscription->user_id)->first();
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
    $currentDateTime = Carbon::now();

    // Ensure end_date is cast to a Carbon instance
    $competitionEndDate = Carbon::parse($competition->end_date);

    // Check if the end date plus one day has passed
    if ($currentDateTime->greaterThan($competitionEndDate->addDay())) {
        $result = $this->fetchCompetitionCounters($competition_id);
        return response()->json($result);
    }

    return response()->json(['message' => 'Competition is still ongoing.'], 403);
}

private function fetchCompetitionCounters($competition_id)
{
    $counters = CompetitionCounterSalawat::with('subscription.user')
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
                'total_counter_value' => $totalCounterValue
            ];
        })
        ->values();

    // Sort the results by total_counter_value from largest to smallest
    $sortedCounters = $counters->sortByDesc('total_counter_value')->values()->toArray();

    return $sortedCounters;
}
}
