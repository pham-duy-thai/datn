<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_record_id',
        'modality',
        'body_part',
        'image_path',
        'annotated_image_path',
        'analysis_status',
        'findings',
        'summary',
        'note',
        'analyzed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    protected function casts(): array
    {
        return [
            'findings' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }
}
