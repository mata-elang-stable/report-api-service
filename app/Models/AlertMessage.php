<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification_id',
        'alert_message',
    ];

    public function alertMetrics()
    {
        return $this->hasMany(AlertMetric::class, 'alert_id');
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }
}
