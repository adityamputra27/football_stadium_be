<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationMark extends Model
{
    use HasFactory;

    public $table = 'notification_marks';
    protected $fillable = ['notification_id', 'user_id', 'mark_status'];
}
