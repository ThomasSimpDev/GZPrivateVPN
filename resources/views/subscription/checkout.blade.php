<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Upgrade to GZPrivateVPN Premium') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-100">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-8 rounded-lg shadow-sm">
                <form action="{{ route('subscription.process') }}" method="POST">
                    @csrf
                    
                    <h3 class="text-lg font-bold mb-4">1. Select Your Subscription Plan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <label class="border-2 rounded-lg p-4 flex items-center justify-between cursor-pointer hover:border-indigo-500">
                            <div>
                                <input type="radio" name="plan" value="monthly" checked class="text-indigo-600">
                                <span class="font-bold ml-2">Monthly VIP Pass</span>
                            </div>
                            <span class="text-xl font-extrabold text-indigo-600">$9.99 / mo</span>
                        </label>
                        
                        <label class="border-2 rounded-lg p-4 flex items-center justify-between cursor-pointer hover:border-indigo-500">
                            <div>
                                <input type="radio" name="plan" value="yearly" class="text-indigo-600">
                                <span class="font-bold ml-2">Annual Access (Save 40%)</span>
                            </div>
                            <span class="text-xl font-extrabold text-indigo-600">$69.99 / yr</span>
                        </label>
                    </div>

                    <h3 class="text-lg font-bold mb-4">2. Payment Details (Mock Provider)</h3>
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Card Number</label>
                            <input type="text" name="card_number" required placeholder="4242 4242 4242 4242" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-md shadow transition">
                        Complete Secure Purchase
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>