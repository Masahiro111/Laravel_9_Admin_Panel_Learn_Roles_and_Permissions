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
