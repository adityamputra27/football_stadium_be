<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FootballClub extends Model
{
    use HasFactory;
    
    public $table = 'football_clubs';
    protected $fillable = ['football_league_id', 'name', 'logo_primary', 'logo_white', 'visit_count', 'status'];
    
    public function footballLeague() : BelongsTo
    {
        return $this->belongsTo(FootballLeague::class);
    }
}
