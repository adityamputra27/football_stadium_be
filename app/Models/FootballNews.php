<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FootballNews extends Model
{
    use HasFactory;

    public $table = 'football_news';
    protected $fillable = ['id', 'title', 'body', 'category', 'is_featured_news', 'image',];
}
