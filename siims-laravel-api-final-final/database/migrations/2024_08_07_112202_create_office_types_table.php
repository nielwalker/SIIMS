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
        // office_types table
        // * Each office_type is assigned to an office
        Schema::create('office_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unique('name');
            $table->timestamps();
        });

        // offices table
        // * Each office has an office type
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_type_id')->constrained(
                table: 'office_types'  
            );
            $table->foreignId('company_id')->nullable()->constrained(
                table: 'companies'
            );
            $table->foreignId('supervisor_id')->nullable()->constrained(
                table: 'users'
            );
            $table->string('name')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->string('street', 100)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('city_municipality', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('offices');
        Schema::dropIfExists('office_types');
        
    }
};
