<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffOnboardingForm extends Model
{
    protected $fillable = [
        'user_id',
        'address', 'phone', 'national_insurance', 'emergency_contact',
        'position', 'start_date', 'supervisor', 'employment_type',
        'previous_experience', 'qualifications', 'driving_licence', 'own_transport',
        'medical_information',
        'criminal_convictions', 'criminal_details',
        'dbs_consent', 'dbs_signed_date',
        'bank_account_name', 'bank_sort_code', 'bank_account_number',
        'doc_id', 'doc_proof_of_address', 'doc_cis_utr', 'doc_tickets',
        'declaration_signed_date',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'              => 'date',
            'dbs_signed_date'         => 'date',
            'declaration_signed_date' => 'date',
            'driving_licence'         => 'boolean',
            'own_transport'           => 'boolean',
            'criminal_convictions'    => 'boolean',
            'dbs_consent'             => 'boolean',
            'doc_id'                  => 'boolean',
            'doc_proof_of_address'    => 'boolean',
            'doc_cis_utr'             => 'boolean',
            'doc_tickets'             => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
