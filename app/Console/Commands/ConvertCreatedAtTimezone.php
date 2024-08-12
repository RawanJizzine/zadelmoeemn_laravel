<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ConvertCreatedAtTimezone extends Command
{
    // Define the command signature and description
    protected $signature = 'update:created_at_timezone {timezone}';
    protected $description = 'Update created_at timestamps to the specified timezone for all records';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $timezone = $this->argument('timezone');
        $tables = DB::select('SHOW TABLES');
        $databaseName = env('DB_DATABASE');

        foreach ($tables as $table) {
            $tableName = $table->{"Tables_in_$databaseName"};

            if (!Schema::hasColumn($tableName, 'created_at')) {
                continue;
            }

            $records = DB::table($tableName)->whereNotNull('created_at')->get();

            foreach ($records as $record) {
                $createdAt = Carbon::parse($record->created_at, 'UTC')->setTimezone($timezone);
                DB::table($tableName)
                    ->where('id', $record->id)
                    ->update(['created_at' => $createdAt->toDateTimeString()]);
            }
        }

        $this->info('Created_at timestamps updated to ' . $timezone);
    }
}