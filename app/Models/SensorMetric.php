<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorMetric extends Model
{
    use HasFactory;

    protected $primaryKey = ['timestamp', 'sensor_id'];
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'timestamp',
        'sensor_id',
        'count',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}
