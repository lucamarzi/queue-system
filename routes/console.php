<?php

 use App\Console\Commands\CloseOpenTickets;
use Illuminate\Support\Facades\Schedule;

 Schedule::command(CloseOpenTickets::class)
        ->dailyAt('16:20')
        ->appendOutputTo(storage_path('logs/tickets-close.log'));