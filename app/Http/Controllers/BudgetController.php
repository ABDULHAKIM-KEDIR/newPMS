<?php

namespace App\Http\Controllers;

use App\Models\Project;

class BudgetController extends Controller
{
    public function index()
    {
        $projects = Project::with('budget')->whereHas('budget')->get();

        return view('budgets.index', compact('projects'));
    }
}
