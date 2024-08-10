<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionCounterSalawat extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'competition_counters_salawat';
    protected $guarded = [];
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
