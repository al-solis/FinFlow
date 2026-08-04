<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {

            $table->id();

            // Identification
            $table->string('organization_code', 20)->unique();
            $table->string('name', 150);
            $table->string('short_name', 50)->nullable();
            $table->string('legal_name', 150);

            // Business Information
            $table->string('industry', 100)->nullable();
            $table->string('business_type', 100)->nullable();

            // Government Registration
            $table->string('tax_id', 50)->nullable();          // TIN
            $table->string('registration_no', 100)->nullable(); // SEC / DTI
            $table->string('tax_branch_code', 20)->nullable(); // Branch Code
            $table->string('bir_rdo_code', 20)->nullable();

            // Description
            $table->text('description')->nullable();

            // Address
            $table->string('address', 255);
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('country', 100)->default('Philippines');
            $table->string('zip_code', 20);

            // Contact
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();

            // Regional Settings
            $table->unsignedBigInteger('currency_id')->nullable()->default(1);
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies');
            $table->string('timezone', 60)->default('Asia/Manila');
            $table->string('language', 30)->default('English');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('number_format', 20)->default('1,234.56');
            $table->tinyInteger('decimal_places')->default(2);

            // Accounting
            $table->enum('accounting_method', [
                'Accrual',
                'Cash'
            ])->default('Accrual');

            // Branding
            $table->string('logo')->nullable();

            // Status
            $table->boolean('status')->default(true);

            // Audit
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users');

            $table->timestamps();
        });

        DB::table('organizations')->insert([
            'organization_code' => 'ORG001',
            'name' => 'FinFlow',
            'short_name' => 'FinFlow',
            'legal_name' => 'FinFlow Inc.',
            'industry' => 'Financial Services',
            'business_type' => 'Corporation',
            'tax_id' => '123-456-789',
            'description' => 'Default organization.',
            'address' => 'Building 123, Main Street',
            'city' => 'Imus',
            'province' => 'Cavite',
            'country' => 'Philippines',
            'zip_code' => '4103',
            'phone' => '+63 (968) 123-4567',
            'email' => 'admin@finflow.local',
            'currency_id' => 1,
            'timezone' => 'Asia/Manila',
            'language' => 'English',
            'date_format' => 'Y-m-d',
            'number_format' => '1,234.56',
            'decimal_places' => 2,
            'accounting_method' => 'Accrual',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};