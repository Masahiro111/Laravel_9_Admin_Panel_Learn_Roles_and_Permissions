<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Update Role name</h1>
                <p class="mt-2 text-sm text-gray-700">Role を更新します</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
            </div>
        </div>

        <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form class="space-y-6" action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" autocomplete="name" value="{{ $role->name }}" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                @error('name')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">更新</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 pt-12 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Assign Permission to Role</h1>
            </div>
        </div>

        <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <div class="flex justify-start items-center">
                        <label for="permissions" class="block text-sm font-medium text-gray-700 pr-2">state:</label>
                        @foreach ($role->permissions as $rp)
                        <span class="inline-flex items-center px-3 py-0.5 mr-1 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ $rp->name }}</span>
                        @endforeach
                    </div>
                    <form class="space-y-6" action="{{ route('admin.roles.permissions', $role) }}" method="POST">
                        @csrf
                        <div>
                            <label for="permissions" class="block text-sm font-medium text-gray-700">Select Permissions</label>
                            <select id="permissions" name="permissions[]" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" multiple>
                                @foreach ($permissions as $permission)
                                <option value="{{ $permission->id }}" @selected($role->hasPermission($permission->name))>
                                    {{ $permission->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">パーミッションを割当</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>