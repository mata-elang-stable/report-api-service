<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Traffic extends Model
{
    use HasFactory;

    protected $table = 'traffics';

    protected $fillable = [
        'timestamp',
        'sensor_id',
        'source_ip',
        'source_port',
        'destination_ip',
        'destination_port',
        'count',
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function sourceIdentity()
    {
        return $this->belongsTo(Identity::class, 'source_ip');
    }

    public function destinationIdentity()
    {
        return $this->belongsTo(Identity::class, 'destination_ip');
    }
}
