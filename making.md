# Laravel 9 Admin Panel - Learn Roles and Permissions （基礎編）

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


## 管理者を作成

`database\migrations\2014_10_12_000000_create_users_table.php` を編集

```diff
    // ...

    return new class extends Migration
    {
        public function up()
        {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
+               $table->boolean('is_admin')->default(0);
                $table->rememberToken();
                $table->timestamps();
            });
        }
    }
```

シーダーファイルを生成。以下のコマンドを入力

```
php artisan make:seeder Admin
```

生成された `database\seeders\Admin.php` を編集

```diff
    // ...

    class Admin extends Seeder
    {
        public function run()
        {
+           User::create([
+               'name' => 'Admin',
+               'email' => 'admin@example.com',
+               'email_verified_at' => now(),
+               'password' => Hash::make('password'),
+               'is_admin' => 1,
+           ]);
        }
    }
```

`database\seeders\DatabaseSeeder.php` を編集

```diff
    public function run()
    {
+       $this->call(Admin::class);
    }
```

管理者情報を登録するために以下のコマンドを入力

```
php artisan migrate:fresh --seed
```

## 管理者用のミドルウェアを作成

管理者のみがアクセスできるようなミドルウェアを作成。以下のコマンドを入力

```
php artisan make:middleware AdminMiddleware
```

`app\Http\Middleware\AdminMiddleware.php` が作成されるので編集。

```diff
    class AdminMiddleware
    {
        public function handle(Request $request, Closure $next)
        {
+           // 認証されていない または、is_admin が 「1」でない場合
+           if (!auth()->user() || !auth()->user()->is_admin) {
+               abort(403);
+           }
            return $next($request);
        }
    }
```

ミドルウェアを使用できるように `Kernel.php` を編集

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

管理者用のコントローラーの作成。以下のコマンドを入力

```
php artisan make:controller AdminController
```

`app\Http\Controllers\AdminController.php` を編集。`index アクション` を編集

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

`resources/views` フォルダに `admin` フォルダを新規作成。そのフォルダ内に `index.blade.php` を作成して以下のように編集。

```html
<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    You're logged in for Admin!
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
```

`app/View/Components/AppLayout.php` をコピーして  `app/View/Components` に `AdminLayout.php` という名前として貼り付けし、以下のように編集

```diff
    <?php

    namespace App\View\Components;

    use Illuminate\View\Component;

    class AdminLayout extends Component
    {
        public function render()
        {
-            return view('layouts.app');
+           return view('layouts.admin');
        }
    }
```

`resources/views/layouts/app.blade.php` をコピーして `resources/views/layouts` に `admin.blade.php` という名前として貼り付け。

`web.php` を編集

```diff
    // ...

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth'])->name('dashboard');

+   Route::middleware(['auth', 'is_admin'])->group(function () {
+       Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
+   });

    require __DIR__ . '/auth.php';
```

## 管理画面の作成

管理画面のメニューに 管理者用ページのリンクを表示する。`resources\views\layouts\navigation.blade.php` を以下のように編集。

```diff
    // ...
    
    <!-- Navigation Links -->
    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-nav-link>

+       @if (auth()->user()->is_admin)
+       <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
+           {{ __('Admin') }}
+       </x-nav-link>
+       @endif
    </div>

    // ...

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

+           @if (auth()->user()->is_admin)
+           <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.index')">
+               {{ __('Admin') }}
+           </x-responsive-nav-link>
+           @endif
        </div>

        // ...
```
