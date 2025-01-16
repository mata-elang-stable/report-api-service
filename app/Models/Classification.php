<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = [
        'priority_id',
        'classification',
    ];

    public function alertMessages()
    {
        return $this->hasMany(AlertMessage::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }
}
