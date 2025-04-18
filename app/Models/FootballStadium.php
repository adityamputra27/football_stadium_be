<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FootballStadium extends Model
{
    use HasFactory;

    public $table = 'football_stadiums';
    protected $fillable = ['football_club_id', 'name', 'capacity', 'country', 'city', 'cost', 'status', 'description'];

    public function footballStadiumFiles(): HasMany 
    {
        return $this->hasMany(FootballStadiumFile::class);
    }

    public function footballClub(): BelongsTo
    {
        return $this->belongsTo(FootballClub::class);
    }
}
