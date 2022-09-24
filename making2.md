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

## 複数の Role を１つのミドルウェアで扱えるようにする

１つのミドルウェアで複数の Role を扱えるように新しくミドルウェアを作成

```
php artisan make:middleware HasRoleMiddleware
```

作成された `app\Http\Middleware\HasRoleMiddleware.php` を編集

```diff
    // ...

    class HasRoleMiddleware
    {
-       public function handle(Request $request, Closure $next)
+       public function handle(Request $request, Closure $next, string $role)
        {
+           if (!auth()->user() || !auth()->user()->hasRole($role)) {
+               abort(403);
+           }
            return $next($request);
        }
    }
```

User モデルに hasRole メソッドを作成。`app\Models\User.php` を編集

```diff
    // ...

    class User extends Authenticatable
    {
        // ...

+       public function hasRole($name): bool
+       {
+           return $this->role()->where('name', $name)->exists();
+       }
    }
```

ミドルウェアを使用できるように `app\Http\Kernel.php` を編集

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
            'is_admin' => AdminMiddleware::class,
+           'role' => HasRoleMiddleware::class,
        ];
    }
```

ルートを編集。`routes\web.php` を編集

```diff
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

+   Route::middleware(['auth', 'role:admin'])->group(function () {
+       Route::get('/admin', [AdminController::class, 'index'])
+           ->name('admin.index');
+   });
+
+   Route::middleware(['auth', 'role:writer'])->group(function () {
+       Route::get('/writers', [AdminController::class, 'index'])
+           ->name('admin.index');
+   });

    require __DIR__ . '/auth.php';
```

ナビゲーションを編集。`resources\views\layouts\navigation.blade.php` を編集

```diff
    // ...

    <!-- Navigation Links -->
    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>

+       @if (Auth::user()->hasRole('admin'))
        <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
            {{ __('Admin') }}
        </x-nav-link>
        @endif
    </div>

    // ...

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

+           @if (Auth::user()->hasRole('admin'))
            <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
                {{ __('Admin') }}
            </x-responsive-nav-link>
            @endif
        </div>
```

## Roles と Permissions のルートを作成

Role の管理画面用のコントローラーを作成。以下のコマンドを入力

```
php artisan make:controller Admin/RoleController
```

Permission の管理画面用のコントローラーを作成。以下のコマンドを入力

```
php artisan make:controller Admin/PermissionController
```

作成した `app\Http\Controllers\Admin\RoleController.php` を編集

```diff
    // ...

    class RoleController extends Controller
    {
+       public function index()
+       {
+           $roles = Role::all();
+
+           return view('admin.roles.index', compact('roles'));
+       }
    }
```

作成した `app\Http\Controllers\Admin\PermissionController.php` を編集

```diff
    // ...

    class PermissionController extends Controller
    {
+       public function index()
+       {
+           $permissions = Permission::all();
+
+           return view('admin.permissions.index', compact('permissions'));
+       }
    }
```

`resources\views\admin\roles\index.blade.php` を新規に作成して以下のように編集

```html
<x-admin-layout>
    <h1>Roles index</h1>
</x-admin-layout>
```

`resources\views\admin\permissions\index.blade.php` を新規に作成して以下のように編集

```html
<x-admin-layout>
    <h1>permission index</h1>
</x-admin-layout>
```

`resources\views\components\admin-layout.blade.php` を編集

```diff
    // ...

    <body>
        <div x-data="{ sidebarOpen: false }" class="flex h-screen bg-gray-200 font-roboto">
            @include('layouts.sidebar')

            <div class="flex-1 flex flex-col overflow-hidden">
                @include('layouts.header')

                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-200">
                    <div class="container mx-auto px-6 py-8">
+                       {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
```

`resources\views\layouts\sidebar.blade.php` を編集

```diff
    // ...
    
    <nav class="mt-10">
        <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('dashboard') }}">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
            <span class="mx-3">Dashboard</span>
        </a>

+       <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('admin.roles.index')}}">
+           <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
+           </svg>
+           <span class="mx-3">Roles</span>
+       </a>
+
+       <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('admin.permissions.index') }}">
+           <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
+           </svg>
+           <span class="mx-3">Permissions</span>
+       </a>

    </nav>
```

ルートを以下のように編集。`routes/web.php` を編集

```php
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])
        ->name('index');

    Route::resource('roles', RoleController::class);

    Route::resource('permissions', PermissionController::class);
});

require __DIR__ . '/auth.php';
```

## Roles と Permissions ページの表示を作成

Roles と Permissions ページのレイアウトを作成する。

`resources\views\admin\roles\index.blade.php` を以下のように編集する

```diff
    <x-admin-layout>
+       <div class="px-4 sm:px-6 lg:px-8">
+           <div class="sm:flex sm:items-center">
+               <div class="sm:flex-auto">
+                   <h1 class="text-xl font-semibold text-gray-900">Roles</h1>
+                   <p class="mt-2 text-sm text-gray-700">Role のリストを表示しています。</p>
+               </div>
+               <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
+                   <button type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add user</button>
+               </div>
+           </div>
+           <div class="-mx-4 mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:-mx-6 md:mx-0 md:rounded-lg">
+               <table class="min-w-full divide-y divide-gray-300">
+                   <thead class="bg-gray-50">
+                       <tr>
+                           <th scope="col" class="py-3.5 pl-4 pr-3 w-1 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
+                           <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
+                           <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
+                               <span class="sr-only">Edit</span>
+                           </th>
+                       </tr>
+                   </thead>
+                   <tbody class="divide-y divide-gray-200 bg-white">
+
+                       @forelse ($roles as $role)
+                       <tr>
+                           <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
+                               {{ $role->id }}
+                           </td>
+                           <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
+                               {{ $role->name }}
+                           </td>
+                           <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
+                               <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>
+                           </td>
+                       </tr>
+                       @empty
+                       @endforelse
+
+                       <!-- More people... -->
+                   </tbody>
+               </table>
+           </div>
+       </div>
    </x-admin-layout>
```

`resources\views\admin\permissions\index.blade.php` を以下のように編集する

```diff
    <x-admin-layout>
+       <div class="px-4 sm:px-6 lg:px-8">
+           <div class="sm:flex sm:items-center">
+               <div class="sm:flex-auto">
+                   <h1 class="text-xl font-semibold text-gray-900">Permissions</h1>
+                   <p class="mt-2 text-sm text-gray-700">Permission のリストを表示しています。</p>
+               </div>
+               <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
+                   <button type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add user</button>
+               </div>
+           </div>
+           <div class="-mx-4 mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:-mx-6 md:mx-0 md:rounded-lg">
+               <table class="min-w-full divide-y divide-gray-300">
+                   <thead class="bg-gray-50">
+                       <tr>
+                           <th scope="col" class="py-3.5 pl-4 pr-3 w-1 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
+                           <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
+                           <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
+                               <span class="sr-only">Edit</span>
+                           </th>
+                       </tr>
+                   </thead>
+                   <tbody class="divide-y divide-gray-200 bg-white">
+
+                       @forelse ($permissions as $permission)
+                       <tr>
+                           <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
+                               {{ $permission->id }}
+                           </td>
+                           <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
+                               {{ $permission->name }}
+                           </td>
+                           <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
+                               <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>
+                           </td>
+                       </tr>
+                       @empty
+                       @endforelse
+
+                       <!-- More people... -->
+                   </tbody>
+               </table>
+           </div>
+       </div>
    </x-admin-layout>
```

## Roles と Permissions ページの新規入力画面を作成する

コントローラーに Role と Permission ページに create アクションを追加。`app\Http\Controllers\Admin\RoleController.php` を編集

```diff
    // ...

    class RoleController extends Controller
    {
        // ...

+       public function create()
+       {
+           return view('admin.roles.create');
+       }
    }
```

`app\Http\Controllers\Admin\PermissionController.php` を編集

```diff
    // ...

    class PermissionController extends Controller
    {
        // ...

+       public function create()
+       {
+           return view('admin.permissions.create');
+       }
}
```

新規に `resources\views\admin\roles\create.blade.php` を作成。以下のように編集

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Roles</h1>
                <p class="mt-2 text-sm text-gray-700">Role を新規作成します</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
            </div>
        </div>
        <h1>Form</h1>
    </div>
</x-admin-layout>
```

新規に `resources\views\admin\permissions\create.blade.php` を作成。以下のように編集

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Permissions</h1>
                <p class="mt-2 text-sm text-gray-700">Permission を新規に作成します</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
            </div>
        </div>
        <h1>Form</h1>
    </div>
</x-admin-layout>
```

新規追加ページのリンクを index.blade.php に追加する。`resources\views\admin\roles\index.blade.php` を編集

```diff
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
-       <button type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add user</button>
+       <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add Role</a>
    </div>
```

`resources\views\admin\permissions\index.blade.php` を編集

```diff
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
-       <button type="button" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add user</button>
+       <a href="{{ route('admin.permissions.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add Permission</a>
    </div>
```

## Role と Permission の新規登録

`app\Http\Controllers\Admin\RoleController.php` を編集。新規 Role を登録する `store` アクションを作成

```diff
    // ...

    class RoleController extends Controller
    {
        // ...

+       public function store(Request $request)
+       {
+           $validated = $request->validate([
+               'name' => 'required|min:3',
+           ]);
+
+           Role::query()->create($validated);
+
+           return redirect()->route('admin.roles.index');
+       }
    }
```

`app\Http\Controllers\Admin\PermissionController.php` を編集。新規 Permission を登録する `store` アクションを作成

```diff
    // ...

    class PermissionController extends Controller
    {
        // ...

+       public function store(Request $request)
+       {
+           $validated = $request->validate([
+               'name' => 'required|min:3',
+           ]);
+
+           Permission::query()->create($validated);
+
+           return redirect()->route('admin.permissions.index');
+       }
    }
```

`resources\views\admin\roles\create.blade.php` を編集。

```diff
    <x-admin-layout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold text-gray-900">Roles</h1>
                    <p class="mt-2 text-sm text-gray-700">Role を新規作成します</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
                </div>
            </div>

+           <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
+               <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
+                   <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
+                       <form class="space-y-6" action="{{ route('admin.roles.store') }}" method="POST">
+                           @csrf
+                           <div>
+                               <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
+                               <div class="mt-1">
+                                   <input id="name" name="name" type="text" autocomplete="name" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
+                                   @error('name')
+                                   <span class="text-sm text-red-500">{{ $message }}</span>
+                                   @enderror
+                               </div>
+                           </div>
+                           <div>
+                               <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">登録</button>
+                           </div>
+                       </form>
+                   </div>
+               </div>
+           </div>

        </div>
    </x-admin-layout>
```

`resources\views\admin\permissions\create.blade.php` を編集。

```diff
    <x-admin-layout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold text-gray-900">Permissions</h1>
                    <p class="mt-2 text-sm text-gray-700">Permission を新規に作成します</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                    <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
                </div>
            </div>

+           <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
+               <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
+                   <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
+                       <form class="space-y-6" action="{{ route('admin.permissions.store') }}" method="POST">
+                           @csrf
+                           <div>
+                               <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
+                               <div class="mt-1">
+                                   <input id="name" name="name" type="text" autocomplete="name" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
+                                   @error('name')
+                                   <span class="text-sm text-red-500">{{ $message }}</span>
+                                   @enderror
+                               </div>
+                           </div>
+                           <div>
+                               <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">登録</button>
+                           </div>
+                       </form>
+                   </div>
+               </div>
+           </div>

        </div>
    </x-admin-layout>
```

## Role と Permission の更新

`app\Http\Controllers\Admin\RoleController.php` を編集

```diff
    // ...

    class RoleController extends Controller
    {
        // ...

+       public function update(Request $request, Role $role)
+       {
+           $validated = $request->validate([
+               'name' => 'required|min:3',
+           ]);
+
+           $role->update($validated);
+
+           return redirect()->route('admin.roles.index');
+       }
    }
```

`app\Http\Controllers\Admin\PermissionController.php` を編集

```diff
    // ...

    class PermissionController extends Controller
    {
        // ...

+       public function update(Request $request, Permission $permission)
+       {
+           $validated = $request->validate([
+               'name' => 'required|min:3',
+           ]);
+
+           $permission->update($validated);
+
+           return redirect()->route('admin.permissions.index');
+       }
    }
```

新規に `resources\views\admin\roles\edit.blade.php` を作成。以下のように編集。

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Roles</h1>
                <p class="mt-2 text-sm text-gray-700">Role を新規作成します</p>
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
</x-admin-layout>
```

新規に `resources\views\admin\permissions\edit.blade.php` を作成。以下のように編集。

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Permissions</h1>
                <p class="mt-2 text-sm text-gray-700">Permission を更新します</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.permissions.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
            </div>
        </div>

        <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form class="space-y-6" action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" autocomplete="name" required value="{{ $permission->name }}" class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
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
</x-admin-layout>
```

`resources\views\admin\roles\index.blade.php` を編集

```diff
    // ...

    @forelse ($roles as $role)
    <tr>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $role->id }}
        </td>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $role->name }}
        </td>
        <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
-           <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>
+           <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
        </td>
    </tr>
    @empty
    @endforelse
```

`resources\views\admin\permissions\index.blade.php` を編集

```diff
    // ...
    
    @forelse ($permissions as $permission)
    <tr>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $permission->id }}
        </td>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $permission->name }}
        </td>
        <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
-           <a href="#" class="text-indigo-600 hover:text-indigo-900">Edit</a>        
+           <a href="{{ route('admin.permissions.edit', $permission) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
        </td>
    </tr>
    @empty
    @endforelse
```

## Role と Permission の削除処理

Role と Permission の削除を行う処理を記入。`app\Http\Controllers\Admin\RoleController.php` を以下のように編集

```diff
    // ...

    class RoleController extends Controller
    {
        public function index()
        {
-           $roles = Role::all();
+           $roles = Role::query()->whereNot('name', 'admin')->latest()->get();

            return view('admin.roles.index', compact('roles'));
        }

        public function create()
        {
            return view('admin.roles.create');
        }

        public function store(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required|min:3',
            ]);

            Role::query()->create($validated);

-           return redirect()->route('admin.roles.index');
+           return redirect()->route('admin.roles.index')
+               ->with('message', 'Role Added!');
        }

        public function edit(Role $role)
        {
            return view('admin.role.edit', compact('role'));
        }

        public function update(Request $request, Role $role)
        {
            $validated = $request->validate([
                'name' => 'required|min:3',
            ]);

            $role->update($validated);
            
-           return redirect()->route('admin.roles.index');
+           return redirect()->route('admin.roles.index')
+               ->with('message', 'Role is updated!');
        }

+       public function destroy(Role $role)
+       {
+           $role->delete();
+
-           return redirect()->route('admin.roles.index');
+           return redirect()->route('admin.roles.index')
+               ->with('message', 'The Role deleted');
+       }
    }
```

`app\Http\Controllers\Admin\PermissionController.php` を以下のように編集

```diff
    // ...

    class PermissionController extends Controller
    {
        public function index()
        {
-           $permissions = Permission::query()->all();
+           $permissions = Permission::query()->latest()->get();

            return view('admin.permissions.index', compact('permissions'));
        }

        public function create()
        {
            return view('admin.permissions.create');
        }

        public function store(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required|min:3',
            ]);

            Permission::query()->create($validated);

-           return redirect()->route('admin.permissions.index');
+           return redirect()->route('admin.permissions.index')
+               ->with('message', 'Permission Added!');
        }

        public function edit(Permission $permission)
        {
            return view('admin.permissions.edit', compact('permission'));
        }

        public function update(Request $request, Permission $permission)
        {
            $validated = $request->validate([
                'name' => 'required|min:3',
            ]);

            $permission->update($validated);

-           return redirect()->route('admin.permissions.index');
+           return redirect()->route('admin.permissions.index')
+               ->with('message', 'Permission is updated!');
        }

+       public function destroy(Permission $permission)
+       {
+           $permission->delete();
+
+           return redirect()->route('admin.permissions.index')
+               ->with('message', 'The Permission deleted');
+       }
    }
```

`resources\views\admin\roles\index.blade.php` を編集。削除用のリンクを作成

```diff
    // ...

    @forelse ($roles as $role)
    <tr>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $role->id }}
        </td>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $role->name }}
        </td>
-       <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
+       <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
            <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
+           <form
+                   method="post"
+                   action="{{ route('admin.roles.destroy', $role) }}"
+                   onsubmit="return confirm('Are you sure?')">
+               @csrf
+               @method('DELETE')
+               <button type="submit" class="text-red-600 hover:text-red-900 pl-2 pr-2">Delete</a>
+           </form>
        </td>
    </tr>
    @empty
    @endforelse

    // ...
```

`resources\views\admin\permissions\index.blade.php` を編集。削除用のリンクを作成

```diff
    // ...

    @forelse ($permissions as $permission)
    <tr>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $permission->id }}
        </td>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $permission->name }}
        </td>
-       <td class="py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
+       <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
            <a href="{{ route('admin.permissions.edit', $permission) }}" class="text-indigo-600 hover:text-indigo-900 pl-2 pr-2">Edit</a>
+           <form
+                   method="post"
+                   action="{{ route('admin.permissions.destroy', $permission) }}"
+                   onsubmit="return confirm('Are you sure?')">
+               @csrf
+               @method('DELETE')
+               <button type="submit" class="text-red-600 hover:text-red-900 pl-2 pr-2">Delete</a>
+           </form>
        </td>
    </tr>
    @empty
    @endforelse

    // ...
```

処理を行ったメッセージを表示するレイアウトを作成。`resources\views\components\admin-layout.blade.php` を編集

```diff
    // ...
    <body>
+       @if (Session::has('message'))
+       <!-- This example requires Tailwind CSS v2.0+ -->
+       <div
+            class="bg-indigo-600"
+            x-data="{ bannerOpen: true }"
+            x-show="bannerOpen">
+           <div class="mx-auto max-w-7xl py-3 px-3 sm:px-6 lg:px-8">
+               <div class="flex flex-wrap items-center justify-between">
+                   <div class="flex w-0 flex-1 items-center">
+                       <p class="ml-3 truncate font-medium text-white">
+                           <span class="md:hidden">{{ Session::get('message') }}</span>
+                           <span class="hidden md:inline">{{ Session::get('message') }}</span>
+                       </p>
+                   </div>
+                   <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-3">
+                       <button
+                               type="button"
+                               class="-mr-1 flex rounded-md p-2 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-white sm:-mr-2"
+                               x-on:click="bannerOpen = false">
+                           <span class="sr-only">Dismiss</span>
+                           <!-- Heroicon name: outline/x-mark -->
+                           <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
+                               <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
+                           </svg>
+                       </button>
+                   </div>
+               </div>
+           </div>
+       </div>
+       @endif

        // ...
``` 

## Permission を Role に割当

`app\Models\Role.php` にリレーション関連のコードを以下のように記入

```diff
    // ...

    class Role extends Model
    {
        // ...

+       public function permissions()
+       {
+           return $this->belongsToMany(Permission::class);
+       }
+
+       public function hasPermission($name): bool
+       {
+           return $this->permissions()->where('name', $name)->exists();
+       }
    }
```

`app\Http\Controllers\Admin\RoleController.php` Role と Permission の多対多の関係を登録するコードを記入。

```diff
    // ...

    class RoleController extends Controller
    {
        // ...

        public function edit(Role $role)
        {
-           return view('admin.role.edit', compact('role'));
+           $permissions = Permission::all();
+           return view('admin.roles.edit', compact('role', 'permissions'));
        }

        // ...

+       public function assignPermissions(Request $request, Role $role)
+       {
+           $role->permissions()->sync($request->permissions);
+           return back()->with('message', 'Permission added');
+       }
    }
```

Permission のセレクトエリアを追記。`resources\views\admin\roles\edit.blade.php` を編集

```diff
    <x-admin-layout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                // ...
            </div>

            <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
                // ...
            </div>
        </div>

+       <div class="px-4 pt-12 sm:px-6 lg:px-8">
+           <div class="sm:flex sm:items-center">
+               <div class="sm:flex-auto">
+                   <h1 class="text-xl font-semibold text-gray-900">Assign Permission to Role</h1>
+               </div>
+           </div>
+
+           <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
+               <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
+                   <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
+                       <div class="flex justify-start items-center">
+                           <label for="permissions" class="block text-sm font-medium text-gray-700 pr-2">state:</label>
+                           @foreach ($role->permissions as $rp)
+                           <span class="inline-flex items-center px-3 py-0.5 mr-1 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ $rp->name }}</span>
+                           @endforeach
+                       </div>
+                       <form class="space-y-6" action="{{ route('admin.roles.permissions', $role) }}" method="POST">
+                           @csrf
+                           <div>
+                               <label for="permissions" class="block text-sm font-medium text-gray-700">Select Permissions</label>
+                               <select id="permissions" name="permissions[]" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm" multiple>
+                                   @foreach ($permissions as $permission)
+                                   <option value="{{ $permission->id }}" @selected($role->hasPermission($permission->name))>
+                                       {{ $permission->name }}
+                                   </option>
+                                   @endforeach
+                               </select>
+                           </div>
+                           <div>
+                               <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">パーミッションを割当</button>
+                           </div>
+                       </form>
+                   </div>
+               </div>
+           </div>
+       </div>
+   </x-admin-layout>
```

`routes\web.php` を編集

```diff
    // ...
    
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])
            ->name('index');

+       Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions'])
+           ->name('roles.permissions');

        Route::resource('/roles', RoleController::class);

        Route::resource('/permissions', PermissionController::class);
    });

    require __DIR__ . '/auth.php';
```

## Users の管理画面を作成

Users を管理するコントローラーを作成。以下のコマンドを入力

```
php artisan make:controller Admin/UserController
```

作成された `app\Http\Controllers\Admin\UserController.php` を編集

```diff
    // ...

    class UserController extends Controller
    {
+       public function index()
+       {
+           $users = User::all();
+
+           return view('admin.users.index', compact('users'));
        }
    }
```

Users を表示するビューを作成。`resources\views\admin\users\index.blade.php` を作成し以下のように編集

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">Users</h1>
                <p class="mt-2 text-sm text-gray-700">User の一覧を表示しています</p>
            </div>
        </div>
        <div class="-mx-4 mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:-mx-6 md:mx-0 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 w-1 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Role</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                            <span class="sr-only">Edit</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">

                    @forelse ($users as $user)
                    <tr>
                        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                            {{ $user->id }}
                        </td>
                        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                            {{ $user->name }}
                        </td>
                        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                            {{ $user->role->name }}
                        </td>
                        <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <form
                                  method="post"
                                  action="{{ route('admin.users.destroy', $user) }}"
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
</x-admin-layout>
```

users のルートを作成。`routes\web.php` を編集

```diff
    // ...

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])
            ->name('index');

        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions'])
            ->name('roles.permissions');

        Route::resource('/roles', RoleController::class);

        Route::resource('/permissions', PermissionController::class);

+       Route::resource('/users', UserController::class);
    });

    require __DIR__ . '/auth.php';
```

サイドバーも users のリンクを記入。`resources\views\layouts\sidebar.blade.php` を編集

```diff
    // ...
    <nav class="mt-10">    
        // ...
        <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('admin.permissions.index') }}">
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>

            <span class="mx-3">Permissions</span>
        </a>

+       <a class="flex items-center px-6 py-2 mt-4 text-gray-100 bg-gray-700 bg-opacity-25" href="{{ route('admin.users.index') }}">
+           <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
+               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
+           </svg>
+
+           <span class="mx-3">Users</span>
+       </a>

    </nav>

    // ...
```

Role ページのプレビューにて、各 Role の Permission を見ることができるようにする。`resources\views\admin\roles\index.blade.php` を編集

```diff
    // ...

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 w-1 text-left text-sm font-semibold text-gray-900 sm:pl-6">ID</th>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Name</th>
+               <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Permissions</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">Edit</span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">

            @forelse ($roles as $role)
            <tr>
                <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                    {{ $role->id }}
                </td>
                <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
                    {{ $role->name }}
                </td>
+               <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
+                   @forelse ($role->permissions as $rp)
+                   <span class="inline-flex items-center px-3 py-0.5 mr-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
+                       {{ $rp->name }}
+                   </span>
+                   @empty
+                   <span class="inline-flex items-center px-3 py-0.5 mr-1 rounded-full text-sm font-medium bg-red-100 text-red-800">No Permissions</span>
+                   @endforelse
+               </td>
                <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                    <form
                            method="post"
                            action="{{ route('admin.roles.destroy', $role) }}"
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

    // ...
```

## User への Role の割当を更新

フォームリクエストを新規に作成。以下のコマンドを入力

```
php artisan make:request UserUpdateRequest
```

作成された `` を以下のように編集

```diff
    // ...

    class UserUpdateRequest extends FormRequest
    {
        public function authorize()
        {
-           return false;
+           return true;
        }

        public function rules()
        {
            return [
+               'name' => ['required'],
+               'email' => ['required', 'email', Rule::unique('users')->ignore($this->user->id)],
+               'role_id' => ['required'],
            ];
        }
    }
```

`app\Http\Controllers\Admin\UserController.php` を以下のように更新

```diff
    // ...

    class UserController extends Controller
    {
        public function index()
        {
-           $users = User::all();
+           $users = User::all()->except(Auth::user()->id);

            return view('admin.users.index', compact('users'));
        }

+       public function edit(User $user)
+       {
+           $roles = Role::all();
+           return view('admin.users.edit', compact('user', 'roles'));
+       }
+
+       public function update(UserUpdateRequest $request, User $user)
+       {
+           $user->update($request->validated());
+
+           return redirect()->route('admin.users.index')
+               ->with('message', 'User updated');
+       }
    }
```

`resources\views\admin\users\edit.blade.php` を新規に作成して以下のように編集

```html
<x-admin-layout>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">User Edit</h1>
                <p class="mt-2 text-sm text-gray-700">User の編集</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Back</a>
            </div>
        </div>

        <div class="flex min-h-full flex-col justify-center sm:px-6 lg:px-8">
            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <form class="space-y-6" action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <div class="mt-1">
                                <input id="name" name="name" type="text" autocomplete="name" value="{{ $user->name }}" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                @error('name')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="text" autocomplete="email" value="{{ $user->email }}" required class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                @error('email')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <label for="roles" class="block text-sm font-medium text-gray-700">Select Role</label>
                            <select id="roles" name="role_id" class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                                @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected($user->hasRole($role->name))>
                                    {{ $role->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="flex w-full justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">更新</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
```

## ユーザーの削除

ユーザーの削除処理を行う。`app\Http\Controllers\Admin\UserController.php` を以下のように編集

```diff
    // ...

    class UserController extends Controller
    {
        // ...

+       public function destroy(User $user)
+       {
+           $user->delete();
+
+           return redirect()->route('admin.users.index')
+               ->with('message', 'User deleted');
+       }
    }
```

## 記事情報の作成

Post モデルとマイグレーションファイルを作成。以下のコマンドを入力

```
php artisan make:model Post -m
```

作成された `database\migrations\2022_09_20_084013_create_posts_table.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
+               $table->string('title');
+               $table->text('body');
                $table->timestamps();
            });
        }

        // ...
    };
```

マイグレートを実行

```
php artisan migrate
```

PostController を作成。以下のコマンドを入力

```
php artisan make:controller PostController
```

記事情報を作成するためのファイルを作成。以下のコマンドを入力

```
php artisan make:factory PostFactory
```

`routes\web.php` を編集

```diff
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

+   Route::resource('/posts', PostController::class);

    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        Route::get('/', [AdminController::class, 'index'])
            ->name('index');

        Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions'])
            ->name('roles.permissions');

        Route::resource('/roles', RoleController::class);

        Route::resource('/permissions', PermissionController::class);

        Route::resource('/users', UserController::class);
    });

    require __DIR__ . '/auth.php';
```

作成された `database\factories\PostFactory.php` を編集

```diff
    // ...

    class PostFactory extends Factory
    {
        public function definition()
        {
            return [
+               'title' => $this->faker->sentence(3),
+               'body' => $this->faker->paragraph(2),
            ];
        }
    }
```

`database\seeders\DatabaseSeeder.php` に記事情報のクラスを追記

```diff
class DatabaseSeeder extends Seeder
{
    public function run()
    {
+       Post::factory(10)->create();
    }
}
```

シードを実行。以下のコマンドを入力

```
php artisan db:seed
```

## 記事情報の表示ページを作成

`app\Http\Controllers\PostController.php` を編集

```diff
    // ...

    class PostController extends Controller
    {
+       public function index()
+       {
+           $posts = Post::all();
+
+           return view('posts.index', compact('posts'));
+       }
    }
```

記事情報のデータを表示するためのページを新規に作成。`resources\views\posts\index.blade.php` を作成して以下のように編集

```html
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
```

使用するレイアウトファイル `resources\views\layouts\guest.blade.php` を編集

```diff
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="csrf-token" content="{{ csrf_token() }}">

            <title>{{ config('app.name', 'Laravel') }}</title>

            <!-- Fonts -->
            <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

+           <script src="https://cdn.tailwindcss.com"></script>
+           <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

            <!-- Scripts -->
-           @vite(['resources/css/app.css', 'resources/js/app.js'])
+           {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
        </head>

        <body>
            <div class="font-sans text-gray-900 antialiased w-2/3 mx-auto">
                {{ $slot }}
            </div>
        </body>

    </html>
```

## ポリシーの作成

記事投稿のポリシーを作成。以下のコマンドを入力

```
php artisan make:policy PostPolicy --model=Post
```

作成された `app\Policies\PostPolicy.php` を編集

```diff
    // ...

    class PostPolicy
    {
        // ...
        public function create(User $user)
        {
            // dd($user->role); // Role モデルインスタンス
            // dd($user->role()); // BelongsTo インスタンス
+           return $user->role->hasPermission('writer');
        }
        // ...
    }
```

記事の新規作成ボタンを表示するかの処理を追加。 `resources\views\posts\index.blade.php` を編集

```diff
    <x-guest-layout>
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-xl font-semibold text-gray-900">Posts</h1>
                    <p class="mt-2 text-sm text-gray-700">Post 情報を表示 </p>
                </div>

+               @can('create', App\Models\Post::class)
+               <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
+                   <a href="{{ route('posts.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Add post</a>
+               </div>
+               @endcan

            </div>

            // ...
```

## 新規記事を作成するためのコントローラー編集

新規に記事を登録するため `app\Http\Controllers\PostController.php` を編集

```diff
    // ...

    class PostController extends Controller
    {
        public function index()
        {
            $posts = Post::all();

            return view('posts.index', compact('posts'));
        }

+       public function create()
+       {
+           $this->authorize('create', Post::class);
+
+           return view('posts.create');
+       }
+
+       public function store(Request $request)
+       {
+           $this->authorize('create', Post::class);
+
+           $validated = $request->validate([
+               'title' => 'required',
+               'body' => 'required',
+           ]);
+
+           Post::query()
+               ->create($validated);
+
+           return redirect()->route('posts.index');
+       }
    }
```

新規記事を記入するフォームを作成。`resources\views\posts\create.blade.php` を作成して以下のように編集

```html
<x-guest-layout>
    <div class="mt-12 max-w-6xl mx-auto">
        @can('create', App\Models\Post::class)
        <div class="flex m-2 p-2">
            <a href="{{ route('posts.index') }}" class="px-4 py-2 bg-indigo-400 hover:bg-indigo-600 rounded">
                Posts Index</a>
        </div>
        @endcan
        <div class="max-w-md mx-auto p-4">
            <form class="space-y-5" method="POST" action="{{ route('posts.store') }}">
                @csrf
                <div>
                    <label for="title" class="text-xl">Title</label>
                    <input id="title" type="text" name="title"
                           class="block w-full py-3 px-3 mt-2
                            text-gray-800 appearance-none
                            border-2 border-gray-100
                            focus:text-gray-500 focus:outline-none focus:border-gray-200 rounded-md" />
                    @error('title')
                    <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="body" class="text-xl">Body</label>
                    <input id="body" type="text" name="body"
                           class="block w-full py-3 px-3 mt-2
                            text-gray-800 appearance-none
                            border-2 border-gray-100
                            focus:text-gray-500 focus:outline-none focus:border-gray-200 rounded-md" />
                    @error('body')
                    <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full py-3 mt-10 bg-indigo-400 hover:bg-indigo-600 rounded-md
                        font-medium text-white uppercase
                        focus:outline-none hover:shadow-none">
                    Create
                </button>
            </form>
        </div>

    </div>
</x-guest-layout>
```

`resources\views\posts\create.blade.php` を編集

```diff
    // ...

    class Post extends Model
    {
        use HasFactory;

+       protected $fillable = [
+           'title',
+           'body',
+       ];
    }
```

## スーパーユーザーの記事投稿許可を追加

スーパーユーザー管理者は記事の追加ができるように編集。`app\Policies\PostPolicy.php` を編集

```diff
    // ...

    class PostPolicy
    {
        use HandlesAuthorization;

+       public function before(User $user, $ability)
+       {
+           if ($user->hasRole('admin')) {
+               return true;
+           }
+       }

        // ...
    }
```

## 更新と削除の認証追加

`` を編集

```diff
    // ...

    class PostController extends Controller
    {
        // ...

+       public function edit(Post $post)
+       {
+           $this->authorize('update', Post::class);
+
+           return view('posts.edit', compact('post'));
+       }
+
+       public function update(Request $request, Post $post)
+       {
+           $this->authorize('update', Post::class);
+
+           $validated = $request->validate([
+               'title' => 'required',
+               'body' => 'required',
+           ]);
+
+           $post->update($validated);
+
+           return redirect()->route('posts.index');
+       }
+
+       public function destroy(Post $post)
+       {
+           $this->authorize('delete', Post::class);
+
+           $post->delete();
+
+           return redirect()->route('posts.index');
+       }
    }
```

`app\Policies\PostPolicy.php` を編集

```diff
    // ...

    public function create(User $user)
    {
        // dd($user->role); // Role モデルインスタンス
        // dd($user->role()); // BelongsTo インスタンス
-       return $user->role->hasPermission('writer');
+       return $user->role->hasPermission('create');
    }

    public function update(User $user, Post $post)
    {
+       return $user->role->hasPermission('update');
    }

    public function delete(User $user, Post $post)
    {
+       return $user->role->hasPermission('delete');
    }

    // ...
}
```

`resources\views\posts\create.blade.php` を編集

```diff
    <x-guest-layout>
        <div class="mt-12 max-w-6xl mx-auto">
-           @can('create', App\Models\Post::class)
+           @can('create')
            <div class="flex m-2 p-2">
                <a href="{{ route('posts.index') }}" class="px-4 py-2 bg-indigo-400 hover:bg-indigo-600 rounded">
                    Posts Index</a>
            </div>

            // ...
    </x-guest-layout>
```

`resources\views\posts\edit.blade.php` を新規に作成して編集

```html
<x-guest-layout>
    <div class="mt-12 max-w-6xl mx-auto">
        @can('update')
        <div class="flex m-2 p-2">
            <a href="{{ route('posts.index') }}" class="px-4 py-2 bg-indigo-400 hover:bg-indigo-600 rounded">
                Posts Index</a>
        </div>
        @endcan
        <div class="max-w-md mx-auto p-4">
            <form class="space-y-5" method="POST" action="{{ route('posts.update', $post) }}">
                @csrf
                @method('PUT')
                <div>
                    <label for="title" class="text-xl">Title</label>
                    <input
                           id="title"
                           type="text"
                           name="title"
                           class="block w-full py-3 px-3 mt-2
                            text-gray-800 appearance-none
                            border-2 border-gray-100
                            focus:text-gray-500 focus:outline-none focus:border-gray-200 rounded-md"
                           value="{{ $post->title }}" />
                    @error('title')
                    <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="body" class="text-xl">Body</label>
                    <input
                           id="body"
                           type="text"
                           name="body"
                           class="block w-full py-3 px-3 mt-2
                            text-gray-800 appearance-none
                            border-2 border-gray-100
                            focus:text-gray-500 focus:outline-none focus:border-gray-200 rounded-md"
                           value="{{ $post->body }}" />
                    @error('body')
                    <span class="text-sm text-red-400">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                        class="w-full py-3 mt-10 bg-indigo-400 hover:bg-indigo-600 rounded-md
                        font-medium text-white uppercase
                        focus:outline-none hover:shadow-none">
                    Update
                </button>
            </form>
        </div>

    </div>
</x-guest-layout>
```

`resources\views\posts\index.blade.php` を編集

```diff
    // ...

    @forelse ($posts as $post)
    <tr>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $post->id }}
        </td>
        <td class="w-full max-w-0 py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:w-auto sm:max-w-none sm:pl-6">
            {{ $post->title }}
        </td>
        <td class="py-4 pl-3 pr-4 flex justify-end text-right text-sm font-medium sm:pr-6">
+           @can('update', $post)
            <a href="{{ route('posts.edit', $post) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
+           @endcan

+           @can('delete', $post)
            <form
                    method="post"
                    action="{{ route('posts.destroy', $post) }}"
                    onsubmit="return confirm('Are you sure?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 hover:text-red-900 pl-2 pr-2">Delete</a>
            </form>
+           @endcan
        </td>
    </tr>
    @empty
    @endforelse

    // ...
```
