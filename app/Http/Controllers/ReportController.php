<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use App\Models\Classification;
use App\Models\Identity;
use App\Models\Sensor;
use App\Models\AlertMessage;
use App\Models\AlertMetric;
use App\Models\SensorMetric;
use App\Models\Traffic;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /**
     * Get the complete report as a JSON object.
     *
     * @return JsonResponse
     */
    public function getReport(): JsonResponse
    {
        $priorities = Priority::with('classifications')->get();
        $classifications = Classification::with('alertMessages')->get();
        $identities = Identity::all();
        $sensors = Sensor::with(['alertMetrics', 'sensorMetrics'])->get();
        $alertMessages = AlertMessage::with('alertMetrics')->get();
        $alertMetrics = AlertMetric::all();
        $sensorMetrics = SensorMetric::all();
        $traffics = Traffic::with(['sensor', 'sourceIdentity', 'destinationIdentity'])->get();

        $report = [
            'priorities' => $priorities,
            'classifications' => $classifications,
            'identities' => $identities,
            'sensors' => $sensors,
            'alertMessages' => $alertMessages,
            'alertMetrics' => $alertMetrics,
            'sensorMetrics' => $sensorMetrics,
            'traffics' => $traffics,
        ];

        return response()->json($report);
    }
}
