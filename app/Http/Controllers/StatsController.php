<?php

namespace App\Http\Controllers;

use App\Models\ReservationHistory;
use Illuminate\Http\Request;
use App\Models\SpaceTrip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Esta clase sirve para sacar estadísticas, o sea, datos curiosos y resúmenes de los viajes y reservas
class StatsController extends Controller
{

    // Saca los 5 viajes más reservados en un periodo (día, mes o año)
    private function getTopTrips($period, $date, $month, $year) {
        $query = ReservationHistory::with('trip')
            ->select('trip_id', DB::raw('SUM(quantity) as total_reservations'));
    
        // Aquí filtramos los datos según el periodo que nos interesa
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        // Agrupamos por viaje, ordenamos por los más reservados y cogemos solo 5
        return $query->groupBy('trip_id')
            ->orderByDesc('total_reservations')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->trip_id,
                    'name' => $item->trip->name ?? 'Sin nombre',
                    'reservations' => $item->total_reservations
                ];
            });
    }
    
    // Esta función ayuda a filtrar los datos según el día, el mes o el año que queremos mirar
    private function applyDateFilters($query, $period, $date, $month, $year) {
        $query->when($period === 'day', function ($q) use ($date) {
            $q->whereBetween('reservation_histories.created_at', [
                $date->copy()->startOfDay()->toDateTimeString(),
                $date->copy()->endOfDay()->toDateTimeString()
            ]);
        })        
        ->when($period === 'month', function ($q) use ($month) {
            $q->whereBetween('reservation_histories.created_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            ]);
        })
        ->when($period === 'year', function ($q) use ($year) {
            $q->whereBetween('reservation_histories.created_at', [
                Carbon::create($year, 1, 1)->startOfYear(),
                Carbon::create($year, 12, 31)->endOfYear()
            ]);
        });
    }    

    // Saca las 5 empresas que más han comprado viajes en un periodo
    private function getTopCompanies($period, $date, $month, $year) {
        $query = ReservationHistory::select([
                'users.id as company_id',
                'users.name as company_name',
                DB::raw('SUM(reservation_histories.quantity) as total_purchases')
            ])
            ->join('users', 'reservation_histories.user_id', '=', 'users.id')
            ->where('users.role', 'company');
    
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        return $query->groupBy('users.id', 'users.name')
            ->orderByDesc('total_purchases')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->company_id,
                    'name' => $item->company_name,
                    'purchases' => $item->total_purchases
                ];
            });
    }

    // Saca cómo han ido las reservas a lo largo del tiempo (por horas, días o meses)
    private function getTrendData($period, $date, $month, $year) {
        $query = ReservationHistory::query()
            ->select(
                DB::raw('COUNT(*) as total'),
                $this->getDateGrouping($period)
            )
            ->groupBy('period_group');
    
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        return $query->get()->map(function ($item) {
            return [
                'label' => $item->period_group,
                'total' => $item->total
            ];
        });
    }
    
    // Esta función junta todos los datos anteriores y los devuelve para mostrarlos en la web
    public function getAdvancedStats(Request $request) {
        $period = $request->input('period');
        $date = Carbon::parse($request->input('date'))->timezone('UTC');
        $month = Carbon::parse($request->input('month'))->timezone('UTC');
        $year = $request->input('year');
    
        return response()->json([
            'topTrips' => $this->getTopTrips($period, $date, $month, $year),
            'topCompanies' => $this->getTopCompanies($period, $date, $month, $year),
            'allTrips' => $period === 'day' ? $this->getAllTrips($period, $date, $month, $year) : [],
            'allCompanies' => $period === 'day' ? $this->getAllCompanies($period, $date, $month, $year) : [],
            'trendData' => $this->getTrendData($period, $date, $month, $year),
            'companyReservationsTrend' => $this->getCompanyReservationsTrend($period, $date, $month, $year),
        ]);
    }

    // Esta función sirve para agrupar los datos según el periodo (por horas, días o meses)
    private function getDateGrouping($period) {
        return match($period) {
            'day' => DB::raw("TO_CHAR(reservation_histories.created_at, 'HH24:00') as period_group"),
            'month' => DB::raw("EXTRACT(DAY FROM reservation_histories.created_at) as period_group"),
            'year' => DB::raw("EXTRACT(MONTH FROM reservation_histories.created_at) as period_group"),
            default => DB::raw("TO_CHAR(reservation_histories.created_at, 'YYYY-MM-DD') as period_group")
        };
    }
          
    // Saca todos los viajes reservados en un periodo, no solo los 5 primeros
    private function getAllTrips($period, $date, $month, $year) {
        $query = ReservationHistory::with('trip')
            ->select('trip_id', DB::raw('SUM(quantity) as total_reservations'));
    
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        return $query->groupBy('trip_id')
            ->orderByDesc('total_reservations')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->trip_id,
                    'name' => $item->trip->name ?? 'Sin nombre',
                    'reservations' => $item->total_reservations
                ];
            });
    }

    // Saca todas las empresas que han hecho reservas en un periodo, no solo las 5 primeras
    private function getAllCompanies($period, $date, $month, $year) {
        $query = ReservationHistory::select([
                'users.id as company_id',
                'users.name as company_name',
                DB::raw('SUM(reservation_histories.quantity) as total_purchases')
            ])
            ->join('users', 'reservation_histories.user_id', '=', 'users.id')
            ->where('users.role', 'company');
    
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        return $query->groupBy('users.id', 'users.name')
            ->orderByDesc('total_purchases')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->company_id,
                    'name' => $item->company_name,
                    'purchases' => $item->total_purchases
                ];
            });
    }

    // Saca cómo han ido las reservas hechas por empresas a lo largo del tiempo
    private function getCompanyReservationsTrend($period, $date, $month, $year) {
        $query = ReservationHistory::query()
            ->join('users', 'reservation_histories.user_id', '=', 'users.id')
            ->where('users.role', 'company')
            ->select(
                DB::raw('COALESCE(SUM(quantity), 0) as total'), // Si no hay nada, pone cero
                $this->getDateGrouping($period)
        );
    
        $this->applyDateFilters($query, $period, $date, $month, $year);
    
        return $query->groupBy('period_group')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->period_group,
                    'total' => $item->total
                ];
            });
    }
    
}