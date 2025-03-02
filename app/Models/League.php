<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    use HasFactory;

    public $table = 'leagues';
    protected $fillable = ['name', 'logo_primary', 'logo_white', 'visit_count', 'status'];
}
