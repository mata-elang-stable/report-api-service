<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        return Report::all();
        // return view('dashboard')->with('reports', $reports)->route('generate.report');
    }

    public function index()
    {
        $reports = Report::all();

        return view('dashboard', compact('reports'));
    }

    public function getReports()
    {
        // $docs = Report::all();
        // return Response()->json([
        //     'reports' => $docs
        // ]);
        $reports = Report::all();

        return view('reports')->with('reports', $reports);
    }
}
