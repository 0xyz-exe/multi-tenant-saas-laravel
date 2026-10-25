<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Plans & Entitlements — ') . $tenant->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Organization Status Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Workspace Status: <span class="capitalize {{ $tenant->status === 'active' ? 'text-green-600' : 'text-red-600' }}">{{ $tenant->status }}</span></h3>
                    <p class="text-xs text-gray-500 mt-1">Suspended workspaces block all project creation and activity.</p>
                </div>
                <form action="{{ route('plans.status') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 border {{ $tenant->status === 'active' ? 'border-red-300 text-red-600 hover:bg-red-50' : 'border-green-300 text-green-600 hover:bg-green-50' }} rounded text-xs font-bold">
                        {{ $tenant->status === 'active' ? 'Suspend Organization' : 'Reactivate Organization' }}
                    </button>
                </form>
            </div>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($plans as $key => $p)
                    <div class="bg-white p-6 rounded-lg shadow-sm border {{ $tenant->plan === $key ? 'border-indigo-600 ring-2 ring-indigo-600' : 'border-gray-200' }} flex flex-col justify-between">
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">{{ $p['name'] }}</h4>
                            <div class="mt-4 flex items-baseline text-gray-900">
                                <span class="text-4xl font-extrabold">${{ $p['price'] }}</span>
                                <span class="ms-1 text-sm font-semibold text-gray-500">/mo</span>
                            </div>

                            <ul class="mt-6 space-y-3 text-sm text-gray-600">
                                <li class="flex items-center">
                                    <span class="font-bold text-gray-900 mr-1">{{ $p['max_members'] }}</span> Max Team Members
                                </li>
                                <li class="flex items-center">
                                    <span class="font-bold text-gray-900 mr-1">{{ $p['max_projects'] }}</span> Concurrent Projects
                                </li>
                                <li class="flex items-center">
                                    <span class="font-bold text-gray-900 mr-1">{{ number_format($p['storage_mb']) }} MB</span> Encrypted Storage
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8">
                            @if ($tenant->plan === $key)
                                <button disabled class="w-full py-2 bg-gray-100 text-gray-400 font-semibold rounded text-sm cursor-not-allowed">Current Plan</button>
                            @else
                                <form action="{{ route('plans.upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $key }}">
                                    <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">Switch to {{ $p['name'] }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
