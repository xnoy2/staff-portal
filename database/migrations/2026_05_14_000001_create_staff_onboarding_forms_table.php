<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_onboarding_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            // Employee Details
            $table->string('address', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('national_insurance', 20)->nullable();
            $table->string('emergency_contact', 200)->nullable();

            // Job Information
            $table->string('position', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->string('supervisor', 100)->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'self_employed'])->nullable();

            // Experience
            $table->text('previous_experience')->nullable();
            $table->text('qualifications')->nullable();
            $table->boolean('driving_licence')->nullable();
            $table->boolean('own_transport')->nullable();

            // Medical
            $table->text('medical_information')->nullable();

            // Criminal
            $table->boolean('criminal_convictions')->nullable();
            $table->text('criminal_details')->nullable();

            // DBS
            $table->boolean('dbs_consent')->default(false);
            $table->date('dbs_signed_date')->nullable();

            // Bank Details
            $table->string('bank_account_name', 100)->nullable();
            $table->string('bank_sort_code', 10)->nullable();
            $table->string('bank_account_number', 20)->nullable();

            // Documents checklist
            $table->boolean('doc_id')->default(false);
            $table->boolean('doc_proof_of_address')->default(false);
            $table->boolean('doc_cis_utr')->default(false);
            $table->boolean('doc_tickets')->default(false);

            // Declaration
            $table->date('declaration_signed_date')->nullable();

            $table->foreignUuid('created_by')->constrained('users');
            $table->foreignUuid('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique('user_id'); // one form per staff member
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_onboarding_forms');
    }
};
