<?php

namespace App\Http\Middleware;

use App\Models\Station;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStationAccess
{
    /**
     * Verifica che l'utente abbia accesso a una postazione
     */
    public function handle(Request $request, Closure $next): Response
    {
        $stationId = $request->session()->get('station_id');
        
        if (!$stationId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Nessuna postazione associata a questo operatore'], 401);
            }
            
            return redirect()->route('station.index')
                ->with('error', 'Nessuna postazione selezionata');
        }
        
        $station = Station::find($stationId);
        
        if (!$station) {
            $request->session()->forget('station_id');
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Postazione non trovata'], 404);
            }
            
            return redirect()->route('station.index')
                ->with('error', 'Postazione non trovata');
        }
        
        // Verifica se la postazione è bloccata
        if ($station->is_locked) {
            $request->session()->forget('station_id');
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Questa postazione è stata bloccata. Contatta l\'amministratore.'], 403);
            }
            
            return redirect()->route('station.index')
                ->with('error', 'Questa postazione è stata bloccata. Contatta l\'amministratore.');
        }
        
        // Condividi la postazione con la vista
        $request->attributes->add(['station' => $station]);
        
        return $next($request);
    }
}