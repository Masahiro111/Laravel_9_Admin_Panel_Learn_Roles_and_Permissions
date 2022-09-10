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