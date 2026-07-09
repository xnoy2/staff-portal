<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAgreement extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'version',
        'duration_years', 'radius_miles', 'status',
        'issued_by', 'issued_at', 'acknowledged_at', 'acknowledged_ip',
        'acknowledged_name', 'decline_reason',
    ];

    protected $casts = [
        'issued_at'       => 'datetime',
        'acknowledged_at' => 'datetime',
        'duration_years'  => 'integer',
        'radius_miles'    => 'integer',
        'version'         => 'integer',
    ];

    public const TYPES = [
        'non_compete'     => 'Non-Compete / Restrictive Covenant',
        'confidentiality' => 'Confidentiality Agreement',
        'general_tc'      => 'General Terms & Conditions',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    /**
     * Build the default non-compete / restrictive-covenant body text, with the
     * duration and radius interpolated. HR can edit this before issuing; whatever
     * is issued is snapshotted onto the record so the signed version is immutable.
     *
     * NOTE: this is a plain-language template, not legal advice. The enforceable
     * wording should be reviewed by an employment solicitor.
     */
    public static function defaultBody(string $company, int $years, int $miles): string
    {
        return trim("
This Restrictive Covenant Agreement is made between {$company} (\"the Company\") and the employee named below (\"the Employee\").

In consideration of the Employee's employment and access to the Company's confidential information, trade methods, client relationships, designs, materials and know-how, the Employee agrees to the following:

1. Confidentiality of Materials & Methods
The Employee shall not, during employment or at any time afterwards, use, copy, reproduce or disclose to any third party the Company's materials, designs, drawings, processes, pricing, supplier information, client lists or methods of work, save as required to perform their duties for the Company.

2. Non-Compete
For a period of {$years} year(s) following the end of employment (howsoever terminated), the Employee shall not, within a radius of {$miles} mile(s) of the Company's principal place of business, directly or indirectly:
   (a) carry on, be employed by, or be engaged in any business that competes with the Company by providing the same or substantially similar products or services; or
   (b) solicit, canvass or deal with any client or customer of the Company with whom the Employee had dealings during their employment.

3. Non-Solicitation of Staff
For the same period, the Employee shall not solicit or entice away any employee or subcontractor of the Company.

4. Reasonableness & Severance
The Employee agrees that these restrictions are reasonable and necessary to protect the Company's legitimate business interests. If any restriction is found to be unenforceable, the remaining restrictions shall continue in full force, and any restriction may be read down to the extent necessary to be enforceable.

5. Acknowledgement
By signing electronically below, the Employee confirms that they have read, understood and agree to be bound by the terms of this Agreement.
        ");
    }
}
