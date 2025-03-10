<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Ticket;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_reception',
        'ticket_prefix',
        'waiting_count'
    ];

    protected $casts = [
        'is_reception' => 'boolean',
    ];

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'service_id');
    }
    
    public function currentTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'current_service_id');
    }
    
    public function getWaitingTickets()
    {
        return $this->currentTickets()
            ->where('status', Ticket::STATUS_WAITING)
            ->orderBy('created_at')
            ->get();
    }
    
    public function getNextTicket()
    {
        return $this->currentTickets()
            ->where('status', Ticket::STATUS_WAITING)
            ->orderBy('created_at')
            ->first();
    }
}
