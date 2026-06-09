<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'doctor_id',
        'examined_at',
        'symptoms',
        'diagnosis',
        'treatment',
        'prescription',
        'note',
        'follow_up_date',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function medicalImages(): HasMany
    {
        return $this->hasMany(MedicalImage::class);
    }

    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    protected function casts(): array
    {
        return [
            'examined_at' => 'date',
            'follow_up_date' => 'date',
        ];
    }
}
