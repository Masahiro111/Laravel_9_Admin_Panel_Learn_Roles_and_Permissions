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
