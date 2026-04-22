<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WaiterCall;

class WaiterCallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $waiterCalls = WaiterCall::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.waiter-calls.index', compact('waiterCalls'));
    }

    public function markAsAttended(WaiterCall $waiterCall)
    {
        $waiterCall->markAsAttended();
        return back()->with('success', 'Waiter call marked as attended!');
    }
}