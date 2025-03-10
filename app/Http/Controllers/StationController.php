<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StationController extends Controller
{
    /**
     * Mostra la pagina di selezione della postazione con dropdown
     */
    public function index(): View
    {
        // Ottieni tutti i servizi
        $services = Service::all();
        
        // Ottieni tutte le postazioni organizzate per servizio
        $serviceStations = [];
        
        foreach ($services as $service) {
            $serviceStations[$service->id] = Station::where('service_id', $service->id)->get();
        }
        
        return view('station.index', [
            'services' => $services,
            'serviceStations' => $serviceStations,
        ]);
    }
    
    /**
     * Seleziona una postazione tramite il form
     */
    public function select(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'station_id' => 'required|exists:stations,id',
        ]);
        
        $stationId = $request->input('station_id');
        
        return $this->accessStation($request, $stationId);
    }
    
    /**
     * Accede a una specifica postazione
     */
    public function access(Request $request, $stationId)
    {
        return $this->accessStation($request, $stationId);
    }
    
    /**
     * Logica comune per accedere a una postazione
     */
    private function accessStation(Request $request, $stationId)
    {
        $station = Station::findOrFail($stationId);
        
        // Verifica se la postazione è bloccata
        if ($station->is_locked) {
            return redirect()->route('station.index')
                ->with('error', 'Questa postazione è bloccata. Contatta l\'amministratore.');
        }
        
        // Verifica se la postazione è già attiva (occupata)
        if ($station->status === Station::STATUS_ACTIVE) {
            return redirect()->route('station.index')
                ->with('error', 'Questa postazione è già in uso.');
        }
        
        // Imposta la postazione come in pausa (non attiva)
        $station->status = Station::STATUS_PAUSED;
        $station->save();
        
        // Salva l'ID della postazione in sessione
        $request->session()->put('station_id', $station->id);
        
        // Reindirizza alla dashboard operatore
        return redirect()->route('operator.dashboard');
    }
    
    /**
     * Chiude l'accesso alla postazione
     */
    public function close(Request $request)
    {
        $stationId = $request->session()->get('station_id');
        
        if ($stationId) {
            $station = Station::find($stationId);
            
            if ($station) {
                // Imposta la postazione come chiusa
                $station->status = Station::STATUS_CLOSED;
                $station->save();
            }
            
            // Rimuovi l'ID della postazione dalla sessione
            $request->session()->forget('station_id');
        }
        
        return redirect()->route('station.index')
            ->with('success', 'Postazione chiusa correttamente');
    }
    
    /**
     * API per ottenere le postazioni disponibili per un servizio
     */
    public function getAvailableStations(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);
        
        $serviceId = $request->input('service_id');
        
        $stations = Station::where('service_id', $serviceId)
            ->where('is_locked', false)
            ->where('status', '!=', Station::STATUS_ACTIVE)
            ->get(['id', 'name']);
            
        return response()->json(['stations' => $stations]);
    }
}