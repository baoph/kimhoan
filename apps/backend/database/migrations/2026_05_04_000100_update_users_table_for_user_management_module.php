<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('role');
            });
        }

        if (! Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            });
        }

        // Chuyển role sang enum để khóa cứng 3 vai trò theo yêu cầu nghiệp vụ.
        if (Schema::hasColumn('users', 'role')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff'");
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' && Schema::hasColumn('users', 'role')) {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'staff'");
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_login_at');
            });
        }

        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
