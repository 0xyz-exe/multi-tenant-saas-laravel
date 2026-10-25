<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $tenant->name }}
                </h2>
                <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ ucfirst($tenant->status) }}
                </span>
                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-800">
                    {{ $metrics['plan'] }} Plan
                </span>
            </div>

            <!-- Workspace Switcher & Creator -->
            <div class="flex items-center space-x-2">
                <form action="" method="GET" class="inline-block">
                    <select onchange="if(this.value) { document.getElementById('switch-form-' + this.value).submit(); }" class="text-xs border-gray-300 rounded p-1.5 font-medium">
                        @foreach ($userTenants as $t)
                            <option value="{{ $t->id }}" {{ $t->id === $tenant->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ $t->pivot->role }})
                            </option>
                        @endforeach
                    </select>
                </form>

                @foreach ($userTenants as $t)
                    <form id="switch-form-{{ $t->id }}" action="{{ route('tenants.switch', $t) }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Usage Limits & Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Your Role</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900 capitalize">{{ $metrics['role'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Projects Active</div>
                    <div class="mt-2 text-2xl font-bold text-indigo-600">{{ $metrics['projects_count'] }} / {{ $metrics['max_projects'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Team Members</div>
                    <div class="mt-2 text-2xl font-bold text-blue-600">{{ $metrics['members_count'] }} / {{ $metrics['max_members'] }}</div>
                </div>
                <div class="bg-white p-5 rounded-lg shadow-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Plan Tier</div>
                    <div class="mt-2 text-2xl font-bold text-purple-600">{{ $metrics['plan'] }}</div>
                </div>
            </div>

            <!-- Two Column Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Recent Projects -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-900">Tenant Projects</h3>
                        <a href="{{ route('projects.index') }}" class="text-sm text-indigo-600 hover:underline">Manage All</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($projects as $proj)
                            <div class="py-2.5 flex justify-between items-center text-sm">
                                <div>
                                    <span class="font-semibold text-gray-900">{{ $proj->title }}</span>
                                    <div class="text-xs text-gray-500">{{ $proj->description ?? 'No description' }}</div>
                                </div>
                                <span class="text-xs px-2 py-0.5 rounded bg-green-50 text-green-700 font-semibold uppercase">{{ $proj->status }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 py-3">No projects created yet.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Create New Organization -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-3">Create New Organization</h3>
                    <form action="{{ route('tenants.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Organization Name</label>
                            <input type="text" name="name" placeholder="Acme Subsidiary" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">+ Create Workspace</button>
                    </form>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="font-bold text-gray-900 mb-4">Workspace Audit History</h3>
                <div class="divide-y divide-gray-100 text-sm">
                    @forelse ($audits as $audit)
                        <div class="py-2 flex justify-between items-center">
                            <div>
                                <span class="font-mono text-xs font-bold text-indigo-700">[{{ $audit->action }}]</span>
                                <span class="text-gray-800 ml-2">{{ $audit->description }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ $audit->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 py-2">No audit logs recorded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
