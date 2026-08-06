<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function checkout()
    {
        return view('subscription.checkout');
    }

    public function processPayment(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:monthly,yearly',
            'card_number' => 'required|numeric',
        ]);

        $user = Auth::user();
        $user->update([
            'is_subscribed' => true,
            'plan_type' => $request->plan,
            'subscription_ends_at' => $request->plan === 'yearly' ? now()->addYear() : now()->addMonth(),
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome to GZPrivateVPN Premium!');
    }
}