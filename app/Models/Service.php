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
        'color_code', 
        'icon_class',  
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

    /**
     * Restituisce un colore predefinito se non è stato impostato
     */
    public function getColorAttribute(): string
    {
        return $this->color_code ?? '#3B82F6'; // Default è blue-500 di Tailwind
    }
    
    /**
     * Restituisce un'icona predefinita se non è stata impostata
     */
    public function getIconAttribute(): string
    {
        return $this->icon_class ?? 'fa-ticket'; // Default è un'icona del ticket
    }
}
