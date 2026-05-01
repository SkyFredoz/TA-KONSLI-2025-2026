<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'rfid_uid',
        'name',
        'category',
    ];

    /**
     * Get all monitoring logs for this item.
     */
    public function monitoringLogs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class);
    }

    /**
     * Get the latest monitoring log to determine check-in/check-out status.
     */
    public function latestLog()
    {
        return $this->hasOne(MonitoringLog::class)->latestOfMany('scanned_at');
    }
}
