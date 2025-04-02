<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasFactory;

    public $table = 'notifications';
    protected $fillable = ['title', 'description', 'status', 'category', 'params', 'send_push', 'sent_at', 'sent_at_status', 'meta'];

    public const CATEGORY_WELCOME = 'WELCOME';
    public const CATEGORY_GOODBYE = 'GOODBYE';
    public const CATEGORY_UPDATE = 'UPDATE';
    public const CATEGORY_PROMOTION = 'PROMOTION';
    public const CATEGORY_ALERT = 'ALERT';
    public const CATEGORY_ANNOUNCEMENT = 'ANNOUNCEMENT';
    public const CATEGORY_SOCIAL = 'SOCIAL';
    public const CATEGORY_TRANSACTION = 'TRANSACTION';
    public const CATEGORY_JOB = 'JOB';

    public function notificationMarks(): HasMany 
    {
        return $this->hasMany(NotificationMark::class);
    }
}
