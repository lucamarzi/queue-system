<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'service_id',
        'is_locked'
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    const STATUS_ACTIVE = 'active';   // Disponibile per nuove chiamate
    const STATUS_BUSY = 'busy';       // NUOVO: Occupato con un cliente
    const STATUS_PAUSED = 'paused';   // In pausa
    const STATUS_CLOSED = 'closed';   // Chiuso

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
    
    public function ticketLogs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }
    
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }
    
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
