<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionCounter;
use App\Models\CompetitionCounterSalawat;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CounterController extends Controller
{
    public function saveEstkhfar(Request $request)
    {
        $today = Carbon::now();
        $date = Carbon::now()->format('Y-m-d');


        $validatedData = $request->validate([
            'competition_id' => 'required|string|max:255',
            'user_id' => 'required|string|max:255',
            'counter_value' => 'required|string|max:255',
        ]);

        $subscription = Subscription::where('competition_id', $validatedData['competition_id'])
            ->where('user_id', $validatedData['user_id'])
            ->first();
        $subscriptionId = $subscription->id;
        //////////////////
        $competition = Competition::where('id', $validatedData['competition_id'])
            ->first();

        // Check if competition exists


        // Get today's date
        $today = Carbon::now();

        $date = Carbon::now()->format('Y-m-d');

        if ($date == $competition->start_date ||  $date == $competition->end_date ||       $today->between($competition->start_date, $competition->end_date) && !$today->isFriday()) {
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
        $competitionId = $request->query('competition_id');
        $userId = $request->query('user_id');

        // Perform validation as needed
        if (!$competitionId || !$userId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        // Fetch the counter value from the database
        $today = Carbon::now()->format('Y-m-d');

        // Fetch the counter value for today
        $counterValue = CompetitionCounter::where('competition_id', $competitionId)
            ->where('user_id', $userId)
            ->whereDate('date', $today) // Filter by today's date
            ->value('counter_value');  // Replace 'counter_value' with the actual column name

        if ($counterValue === null) {
            return response()->json(['counter_value' => 0], 200); // Default to 0 if not found
        }

        return response()->json(['counter_value' => $counterValue], 200);
    }

    public function getNatijatiestkhfar(Request $request)
    {
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

        $startDate = $competition->start_date;
        $endDate = $competition->end_date;

        // Fetch the CompetitionCounter records within the date range
        $competitionCounters = CompetitionCounter::where('user_id', $userId)
            ->where('competition_id', $competitionId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return response()->json($competitionCounters);
    }

    public function savesalawat(Request $request)
    {


        $validatedData = $request->validate([
            'competition_id' => 'required|string|max:255',
            'user_id' => 'required|string|max:255',
            'counter_value' => 'required|string|max:255',
        ]);

        $subscription = Subscription::where('competition_id', $validatedData['competition_id'])
            ->where('user_id', $validatedData['user_id'])
            ->first();
        $subscriptionId = $subscription->id;
        //////////////////
        $competition = Competition::where('id', $validatedData['competition_id'])
            ->first();

        // Check if competition exists


        // Get today's date
        $today = Carbon::now();
        $date = Carbon::now()->format('Y-m-d');

        if ($date == $competition->start_date ||  $date == $competition->end_date ||                    $today->between($competition->start_date, $competition->end_date) && $today->isFriday()) {
            $counter = CompetitionCounterSalawat::updateOrCreate(
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
    public function getCounterSalawat(Request $request)
    {
        $competitionId = $request->query('competition_id');
        $userId = $request->query('user_id');

        // Perform validation as needed
        if (!$competitionId || !$userId) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        // Fetch the counter value from the database
        $today = Carbon::now()->format('Y-m-d');

        // Fetch the counter value for today
        $counterValue = CompetitionCounterSalawat::where('competition_id', $competitionId)
            ->where('user_id', $userId)
            ->whereDate('date', $today) // Filter by today's date
            ->value('counter_value');  // Replace 'counter_value' with the actual column name

        if ($counterValue === null) {
            return response()->json(['counter_value' => 0], 200); // Default to 0 if not found
        }

        return response()->json(['counter_value' => $counterValue], 200);
    }
}
