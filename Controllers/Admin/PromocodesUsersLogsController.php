<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PromocodesUsersLogs;

class PromocodesUsersLogsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $promocodesuserslogs = PromocodesUsersLogs::orderBy('created_at', 'desc')->get();
        return view('admin.promocodesuserslogs.show', compact('promocodesuserslogs'));
    }
}
