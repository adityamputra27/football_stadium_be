<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballStadiumFile extends Model
{
    use HasFactory;

    public $table = 'football_stadium_files';
    protected $fillable = ['football_stadium_id', 'file', 'file_ext', 'file_size', 'file_path'];
}
