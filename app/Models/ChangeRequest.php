<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    protected $primaryKey = 'change_request_id';
    public $timestamps = false;

    protected $fillable = [
        'project_id', 'requested_by', 'description', 'status',
        'requested_date', 'approved_by', 'approved_date',
    ];

    protected $casts = [
        'requested_date' => 'datetime',
        'approved_date' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by', 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }
}
