<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Service;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'type',
        'status',
        'service_id',
        'current_service_id',
        'first_called_at',
        'completed_at'
    ];

    protected $casts = [
        'first_called_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Costanti per i tipi di ticket
    const TYPE_INFO = 'info';
    const TYPE_SERVICE = 'service';

     // Costanti per gli stati dei ticket
    const STATUS_WAITING = 'waiting';
    const STATUS_CALLED = 'called';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ABANDONED = 'abandoned';

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function currentService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'current_service_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TicketLog::class);
    }
    
    public function getWaitingTime()
    {
        if (!$this->first_called_at) {
            return null;
        }
        
        return $this->first_called_at->diffInMinutes($this->created_at);
    }
    
    public function getTotalTime()
    {
        if (!$this->completed_at) {
            return null;
        }
        
        return $this->completed_at->diffInMinutes($this->created_at);
    }
}
