<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CookieConsentReport extends Controller
{
    public function showUserReport()
    {         
        return view('admin.reports.cookie-consent-user-report');
    }

    public function getFilteredUsers($filterType)
    {
        $users = \App\Models\User::where('admin', $filterType)->with('userDomains')->orderBy('created_at', 'desc')->paginate(10);
        return response()->json(['users' => $users]);
    }
}
