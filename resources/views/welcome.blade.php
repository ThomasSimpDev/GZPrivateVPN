<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 text-slate-900">
        <!-- Added flex and flex-col here to allow full vertical centering -->
        <div class="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.25),_transparent_35%),linear-gradient(135deg,_#f8fafc_0%,_#e2e8f0_100%)]">
            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 sm:px-8 lg:px-10">
                <a href="{{ url('/') }}" class="text-lg font-semibold tracking-tight text-slate-900">
                    GZPrivateVPN
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
                                Open Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 transition hover:text-slate-900">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-400 hover:text-slate-900">
                                    Create account
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <!-- Centered Main Layout -->
            <main class="mx-auto flex flex-1 w-full max-w-7xl items-center justify-center px-6 pb-16 sm:px-8 lg:px-10">
                <section class="max-w-2xl rounded-3xl border border-slate-200/80 bg-white/90 p-8 shadow-2xl shadow-slate-200/70 backdrop-blur sm:p-10 lg:p-12">
                    <div class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                        Secure private access
                    </div>

                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">
                        Fast, premium VPN access for your everyday connections.
                    </h1>

                    <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                        Manage servers, unlock premium nodes, and keep your traffic flowing with low-latency infrastructure designed for privacy and performance.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-full bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                Go to dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-full bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                Get started
                            </a>
                        @endauth
                        <a href="{{ route('subscription.checkout') }}" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                            Upgrade to Premium
                        </a>
                    </div>

                    <div class="mt-10 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Low latency</p>
                            <p class="mt-1 text-sm text-slate-600">Optimized nodes for smooth browsing and streaming.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Premium tiers</p>
                            <p class="mt-1 text-sm text-slate-600">Unlock advanced servers with a simple upgrade.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Global reach</p>
                            <p class="mt-1 text-sm text-slate-600">Access locations across multiple regions and cities.</p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>