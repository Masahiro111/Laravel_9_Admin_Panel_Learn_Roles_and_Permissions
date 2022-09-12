# Laravel 9 Admin Panel - Learn Roles and Permissions （応用編）

## Laravel のインストール

Laravel のインストーラからインスト―ル。以下のコマンドを入力

```
laravel new project_name --git
```

インストール後にプロジェクトルートへ移動

```
cd project_name
```

env ファイルを編集してデータベース環境を整える。
そのあと、以下のコマンドで初期値のマイグレート処理をする

```
php artisan migrate
```

## Laravel Breeze のインストール

Laravel Breeze のインストールのコマンドを以下のように入力

```
composer require laravel/breeze --dev

php artisan breeze:install

php artisan migrate:fresh

npm install

npm run dev
```

`php artisan breeze:install` を入力すると、npm が実行されて css や js ファイルが生成される。
なので `npm install` と `npm run dev` は入力しなくてもよい。

## Role モデルと Permission モデルの作成

Role モデルの作成をする。以下のコマンドを入力

```
php artisan make:model Role -m
```

作成された `database\migrations\2022_09_09_074312_create_roles_table.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
+               $table->string('name')->unique();
                $table->timestamps();
            });
        }
        // ...
    };
```

Permission モデルの作成をする。以下のコマンドを入力

```
php artisan make:model Permission -m
```

作成された `database\migrations\2022_09_09_074359_create_permissions_table.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
+               $table->string('name')->unique();
                $table->timestamps();
            });
        }
    };
```

Role モデルと Permission モデルの中間テーブルを作成。以下のコマンドを入力

```
php artisan make:migration add_foregin_role_id_for_users --table=users
```

作成された `database\migrations\2022_09_09_074734_create_permission_role_table.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('permission_role', function (Blueprint $table) {
+               $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
+               $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            });
        }

        // ...
    };
```

users テーブルに外部キーとして、`role_id` を設定する。以下のコマンドを入力

```
php artisan make:migration add_foregin_role_id_for_users --table=users
```

作成された `database\migrations\2022_09_09_075025_add_foregin_role_id_for_users.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
+               $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            });
        }

        // ...
    };
```

マイグレートを再実行

```
php artisan migrate:fresh
```

## Admin シーダーの作成

まず、`Role` モデルを編集して 書き込みができるカラムとリレーションを設定する

`app\Models\Role.php`

```diff
    // ...

    class Role extends Model
    {
        use HasFactory;

+       protected $fillable = [
+           'name',
+       ];
+
+       public function users()
+       {
+           return $this->hasMany(User::class);
+       }
    }
```

`app\Models\Permission.php` も同様に編集

```diff
    // ...

    class Permission extends Model
    {
        use HasFactory;

+       protected $fillable = [
+           'name',
+       ];
    }
```

`app\Models\User.php` を編集する。書き込みカラムとリレーションの編集をする

```diff
    // ...

    class User extends Authenticatable
    {
        use HasApiTokens, HasFactory, Notifiable;
        
        protected $fillable = [
            'name',
            'email',
            'password',
+           'role_id',
        ];

        // ...

+       public function role()
+       {
+           return $this->belongsTo(Role::class);
+       }
    }
```

管理者用のシーダーを作成する。以下のコマンドを入力

```
php artisan make:seeder AdminSeeder
```

作成された `database\seeders\AdminSeeder.php` を以下のように編集

```diff
    // ...

    class AdminSeeder extends Seeder
    {
        public function run()
        {
+           $userRole = Role::create([
+               'name' => 'user',
+           ]);
+
+           $adminRole = Role::create([
+               'name' => 'admin',
+           ]);
+
+           User::query()
+               ->create([
+                   'name' => 'Admin',
+                   'email' => 'admin@example.com',
+                   'password' => bcrypt('password'),
+                   'email_verified_at' => now(),
+                   'role_id' => $adminRole->id,
+               ]);
        }
    }
```

シーダーを有効にするため `database\seeders\DatabaseSeeder.php` を編集

```diff
    // ...

    class DatabaseSeeder extends Seeder
    {
        public function run()
        {
+           $this->call(AdminSeeder::class);
        }
    }
```

マイグレーションを再実行する。同時にシーダーのインサート情報も有効にするために以下のコマンドを入力

```
php artisan migrate:fresh --seed
```

新規にユーザーを登録する際は `role_id` の情報をデータベースに登録する必要があるため `app\Http\Controllers\Auth\RegisteredUserController.php` を編集

```diff
class RegisteredUserController extends Controller
{
    // ...

    public function store(Request $request)
    {
        // ...

        $user = User::create([
+           'role_id' => 1,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ...
    }
}
```

## 管理者画面のレイアウト作成

管理者画面用のレイアウトを作成。`resources\views\admin\index.blade.php` を作成。以下のように編集

```html
<x-admin-layout>
    <h1>Admin</h1>
</x-admin-layout>
```

全体の基本的なレイアウトを表示する `resources\views\components\admin-layout.blade.php` を作成。

```html
<!DOCTYPE html>
<html lang="{{ $page->language ?? 'en' }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="referrer" content="always">

        <title>Admin</title>

        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

        {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    </head>

    <body>
        <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-200 font-roboto">
            @include('layouts.sidebar')

            <div class="flex-1 flex flex-col overflow-hidden">
                @include('layouts.header')

                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200">
                    <div class="container mx-auto px-6 py-8">
                        @yield('body')
                    </div>
                </main>
            </div>
        </div>
    </body>

</html>
```

ヘッダー用のレイアウトファイル `resources\views\layouts\header.blade.php` を作成。以下のように編集。

```html
<header class="flex items-center justify-between px-6 py-4 bg-white border-b-4 border-indigo-600">
    <div class="flex items-center">
        <button @click="sidebarOpen = true" class="text-gray-500 focus:outline-none lg:hidden">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

        <div class="relative mx-4 lg:mx-0">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none">
                    <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>

            <input class="w-32 pl-10 pr-4 rounded-md form-input sm:w-64 focus:border-indigo-600" type="text" placeholder="Search">
        </div>
    </div>

    <div class="flex items-center">
        <div x-data="{ notificationOpen: false }" class="relative">
            <button @click="notificationOpen = ! notificationOpen" class="flex mx-4 text-gray-600 focus:outline-none">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <div x-cloak x-show="notificationOpen" @click="notificationOpen = false" class="fixed inset-0 z-10 w-full h-full"></div>

            <div x-cloak x-show="notificationOpen" class="absolute right-0 z-10 mt-2 overflow-hidden bg-white rounded-lg shadow-xl w-80" style="width:20rem;">
                <a href="#" class="flex items-center px-4 py-3 -mx-2 text-gray-600 hover:text-white hover:bg-indigo-600">
                    <img class="object-cover w-8 h-8 mx-1 rounded-full" src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=334&q=80" alt="avatar">
                    <p class="mx-2 text-sm">
                        <span class="font-bold" href="#">Sara Salah</span> replied on the <span class="font-bold text-indigo-400" href="#">Upload Image</span> artical . 2m
                    </p>
                </a>
                <a href="#" class="flex items-center px-4 py-3 -mx-2 text-gray-600 hover:text-white hover:bg-indigo-600">
                    <img class="object-cover w-8 h-8 mx-1 rounded-full" src="https://images.unsplash.com/photo-1531427186611-ecfd6d936c79?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=634&q=80" alt="avatar">
                    <p class="mx-2 text-sm">
                        <span class="font-bold" href="#">Slick Net</span> start following you . 45m
                    </p>
                </a>
                <a href="#" class="flex items-center px-4 py-3 -mx-2 text-gray-600 hover:text-white hover:bg-indigo-600">
                    <img class="object-cover w-8 h-8 mx-1 rounded-full" src="https://images.unsplash.com/photo-1450297350677-623de575f31c?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=334&q=80" alt="avatar">
                    <p class="mx-2 text-sm">
                        <span class="font-bold" href="#">Jane Doe</span> Like Your reply on <span class="font-bold text-indigo-400" href="#">Test with TDD</span> artical . 1h
                    </p>
                </a>
                <a href="#" class="flex items-center px-4 py-3 -mx-2 text-gray-600 hover:text-white hover:bg-indigo-600">
                    <img class="object-cover w-8 h-8 mx-1 rounded-full" src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=398&q=80" alt="avatar">
                    <p class="mx-2 text-sm">
                        <span class="font-bold" href="#">Abigail Bennett</span> start following you . 3h
                    </p>
                </a>
            </div>
        </div>

        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = ! dropdownOpen" class="relative block w-8 h-8 overflow-hidden rounded-full shadow focus:outline-none">
                <img class="object-cover w-full h-full" src="https://images.unsplash.com/photo-1528892952291-009c663ce843?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=296&q=80" alt="Your avatar">
            </button>

            <div x-cloak x-show="dropdownOpen" @click="dropdownOpen = false" class="fixed inset-0 z-10 w-full h-full"></div>

            <div x-cloak x-show="dropdownOpen" class="absolute right-0 z-10 w-48 mt-2 overflow-hidden bg-white rounded-md shadow-xl">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                                           onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</header>
```

サイドバー用のレイアウトファイル `resources\views\layouts\sidebar.blade.php` を作成。以下のように編集。

```html
<div x-cloak :class="sidebarOpen ? 'block' : 'hidden'" @click="sidebarOpen = false" class="fixed inset-0 z-20 transition-opacity bg-black opacity-50 lg:hidden"></div>

<div x-cloak :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto transition duration-300 transform bg-gray-900 lg:translate-x-0 lg:static lg:inset-0">
    <div class="flex items-center justify-center mt-8">
        <div class="flex items-center">
            <svg class="w-12 h-12" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M364.61 390.213C304.625 450.196 207.37 450.196 147.386 390.213C117.394 360.22 102.398 320.911 102.398 281.6C102.398 242.291 117.394 202.981 147.386 172.989C147.386 230.4 153.6 281.6 230.4 307.2C230.4 256 256 102.4 294.4 76.7999C320 128 334.618 142.997 364.608 172.989C394.601 202.981 409.597 242.291 409.597 281.6C409.597 320.911 394.601 360.22 364.61 390.213Z" fill="#4C51BF" stroke="#4C51BF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M201.694 387.105C231.686 417.098 280.312 417.098 310.305 387.105C325.301 372.109 332.8 352.456 332.8 332.8C332.8 313.144 325.301 293.491 310.305 278.495C295.309 263.498 288 256 275.2 230.4C256 243.2 243.201 320 243.201 345.6C201.694 345.6 179.2 332.8 179.2 332.8C179.2 352.456 186.698 372.109 201.694 387.105Z" fill="white" />
            </svg>

            <span class="mx-2 text-2xl font-semibold text-white">Dashboard</span>
        </div>
    </div>

    <nav class="mt-10">
        <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('dashboard') }}">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>

            <span class="mx-3">Dashboard</span>
        </a>

    </nav>
</div>
```

管理者用のコントローラーを作成。以下のコマンドを入力

```
php artisan make:controller Admin/AdminController
```

作成された `app\Http\Controllers\Admin\AdminController.php` を以下のように編集。

```diff
    // ...

    class AdminController extends Controller
    {
+       public function index()
+       {
+           return view('admin.index');
+       }
    }
```

ルート情報を編集。`routes\web.php` を編集

```diff
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

+   Route::middleware(['auth'])->group(function () {
+       Route::get('/admin', [AdminController::class, 'index'])
+           ->name('admin.index');
+   });

    require __DIR__ . '/auth.php';
```

## 管理者用のミドルウェアを作成

管理者のアクセスを制御するミドルウェアを作成。以下のコマンドを入力

```
php artisan make:middleware AdminMiddleware
```

作成された `app\Http\Middleware\AdminMiddleware.php` を編集

```diff
    class AdminMiddleware
    {
        public function handle(Request $request, Closure $next)
        {
+           if (!auth()->user() || !auth()->user()->isAdmin()) {
+               abort(403);
+           }

            return $next($request);
        }
    }
```

`User.php` を編集して `isAdmin` メソッドを作成

```diff
    // ...

    class User extends Authenticatable
    {
        // ...

+       public function isAdmin(): bool
+       {
+           return $this->role()->where('name', 'admin')->exists();
+       }
    }
```

ミドルウェアが有効になるように `app\Http\Kernel.php` を編集

```diff
    // ...

    class Kernel extends HttpKernel
    {
        // ...

        protected $routeMiddleware = [
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
+           'is_admin' => AdminMiddleware::class,
        ];
    }
```

ルート設定でミドルウェアが有効になるように `web.php` を編集

```diff
    // ...

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

-   Route::middleware(['auth'])->group(function () {
+   Route::middleware(['auth', 'is_admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])
            ->name('admin.index');
    });

    require __DIR__ . '/auth.php';
```

管理者のみ管理者ページを閲覧できるように ナビゲーションページのリンクを編集

```diff
    // ...
    <!-- Navigation Links -->
    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>

+       @if (Auth::user()->isAdmin())
+       <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
+           {{ __('Admin') }}
+       </x-nav-link>
+       @endif
    </div>

    // ...

    <div class="pt-2 pb-3 space-y-1">
        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-responsive-nav-link>

+       @if (Auth::user()->isAdmin())
+       <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
+           {{ __('Admin') }}
+       </x-responsive-nav-link>
+       @endif
    </div>

    // ...
```