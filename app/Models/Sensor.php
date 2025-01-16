<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'sensor_name',
    ];

    public function alertMetrics()
    {
        return $this->hasMany(AlertMetric::class);
    }

    public function traffic()
    {
        return $this->hasMany(Traffic::class);
    }

    public function sensorMetrics()
    {
        return $this->hasMany(SensorMetric::class);
    }
}
