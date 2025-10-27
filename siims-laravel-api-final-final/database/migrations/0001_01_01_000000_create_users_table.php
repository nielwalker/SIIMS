<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // users table
        Schema::create('users', function (Blueprint $table) {
            $table->id()->primary()->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('avatar')->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            // $table->string('gender', 10)->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city_municipality', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->rememberToken();
            $table->boolean('is_admin')->default(false);
            $table->timestamp('blocked_at')->nullable();
            /**
             * New Attributes
             */
            $table->string('profile_image_url')->nullable();
            $table->string('cover_image_url')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // password_reset_tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        
        // roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        // user_roles table
        // * Each user have at least one or many roles
        // * Each role is assigned to a user
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();

            // Foreign Key
            $table->foreignId('user_id')->constrained(
                table: 'users'
            )->cascadeOnDelete();
            $table->foreignId('role_id')->constrained(
                table: 'roles'
            )->cascadeOnDelete();
            // Attributes
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Timestamps
            $table->timestamps();
          
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('user_roles');
      
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
