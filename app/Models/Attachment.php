<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $primaryKey = 'attachment_id';
    // Enable timestamps (they now exist in the table)
    // public $timestamps = false;  // remove this line

    protected $fillable = ['entity_type', 'entity_id', 'file_name', 'file_path', 'uploaded_by'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    // Polymorphic parent
    public function attachable()
    {
        return $this->morphTo('attachable', 'entity_type', 'entity_id');
    }
}