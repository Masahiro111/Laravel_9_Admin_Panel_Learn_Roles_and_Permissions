<x-guest-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Posts</h1>
                <p class="mt-2 text-sm text-gray-700">Post 情報を表示 </p>
            </div>
            {{--
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.posts.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add post</a>
            </div>
            --}}
        </div>
        <div class="-mx-4 mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:-mx-6 md:mx-0 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 w-1 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
                        <th scope="col" class="py-3.5 pl-4 pr-3 w-full whitespace-nowrap text-left text-sm font-semibold text-gray-900 sm:pl-6">Title</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 w-1 whitespace-nowrap sm:pr-6">
                            <span class="sr-only">Edit</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">

                    @forelse ($posts as $post)
                    <tr>
                        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                            {{ $post->id }}
                        </td>
                        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                            {{ $post->title }}
                        </td>
                        <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <form
                                  method="post"
                                  action="{{ route('posts.destroy', $post) }}"
                                  onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 pl-2 pr-2">Delete</a>
                            </form>
                        </td>
                    </tr>
                    @empty
                    @endforelse

                    <!-- More people... -->
                </tbody>
            </table>
        </div>
    </div>
</x-guest-layout>