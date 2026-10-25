<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Members & Roles — ') . $tenant->name }}
        </h2>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Invite / Add Member Form -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Add Team Member</h3>
                    <form action="{{ route('members.invite') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Email Address</label>
                            <input type="email" name="email" placeholder="colleague@enterprise.com" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Role</label>
                            <select name="role" class="mt-1 block w-full border-gray-300 rounded text-sm" required>
                                <option value="member">Member (Read & Project creation)</option>
                                <option value="admin">Admin (Manage members & billing)</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded text-sm">+ Add / Invite</button>
                    </form>
                </div>

                <!-- Members Table -->
                <div class="col-span-2 bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-gray-900 mb-4">Workspace Members</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Member</th>
                                    <th class="px-4 py-2 text-center">Role</th>
                                    <th class="px-4 py-2 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($members as $m)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900">{{ $m->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $m->email }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-xs font-bold uppercase
                                                @if($m->pivot->role === 'owner') bg-purple-100 text-purple-800
                                                @elseif($m->pivot->role === 'admin') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $m->pivot->role }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($m->id !== $tenant->created_by && in_array($role, ['owner', 'admin']))
                                                <form action="{{ route('members.remove', $m) }}" method="POST" onsubmit="return confirm('Remove user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-900 font-bold">Remove</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
