<?php

namespace App\Http\Controllers\Admin\New;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PopularWorkoutController extends Controller
{
    public function index()
    {
        return view('admin.popularworkouts.index');
    }
}
