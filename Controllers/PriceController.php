<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PriceController extends Controller
{
    public function getPricing(){
        $activePlans = \App\Models\MembershipPlans::where('enabled', 1)->orderBy('sort_order', 'asc')->get();
        return view('price-plan-new')->with(['activePlans' => $activePlans]);
    }
}
