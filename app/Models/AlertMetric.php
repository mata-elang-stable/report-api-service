<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'timestamp',
        'sensor_id',
        'alert_id',
        'count',
    ];

    public function alertMessage()
    {
        return $this->belongsTo(AlertMessage::class, 'alert_id');
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id', 'id');
    }
}
