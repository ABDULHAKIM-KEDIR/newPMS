<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $primaryKey = 'notification_id';
    public $timestamps = false;

    protected $fillable = ['user_id', 'message', 'is_read', 'type'];

    protected $casts = ['is_read' => 'boolean', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
