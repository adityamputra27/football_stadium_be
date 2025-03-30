<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FootballLeague extends Model
{
    use HasFactory;

    public $table = 'football_leagues';
    protected $fillable = ['name', 'logo_primary', 'logo_white', 'visit_count', 'status'];

    public function footballClubs() : HasMany
    {
        return $this->hasMany(FootballClub::class);
    }
}
