<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HandlesTimezone;
class Competition extends Model
{
    use HasFactory, HandlesTimezone;
    protected $table = 'competitions';
    protected $guarded = [];
}
