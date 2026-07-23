<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(
            'users',
            function (Blueprint $table) {
                $table->id();
                $table->string('employee_id', 10)->unique();
                $table->string('last_name', 50);
                $table->string('first_name', 50);
                $table->string('middle_name', 50)->nullable();
                $table->string('email')->unique();
                $table->unsignedBigInteger('role_id');
                $table->foreign('role_id')->references('id')->on('roles');
                $table->string('profile_picture')->nullable();
                $table->string('department_id', 10)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            }
        );

        DB::table('users')->insert([
            [
                'employee_id' => 'EMP001',
                'last_name' => 'Solis',
                'first_name' => 'Al',
                'middle_name' => 'B.',
                'email' => 'admin@yahoo.com',
                'role_id' => 1,
                'department_id' => 'DPT001',
                'password' => bcrypt('admin123'),
            ],
        ]);

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
