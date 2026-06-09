<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'medical_record_id',
        'name',
        'performed_at',
        'result',
        'file_path',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
        ];
    }
}
