<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The prepared paperwork. action_reference holds the ordered {field, from, to}
 * rows exactly as the print view consumes them.
 */
class PanForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'pan_request_id', 'date_hired', 'employment_status', 'doe_from',
        'doe_to', 'wage_no', 'action_reference', 'remarks', 'prepared_by',
    ];

    protected function casts(): array
    {
        return [
            'date_hired' => 'date',
            'employment_status' => EmploymentStatus::class,
            'doe_from' => 'date',
            'doe_to' => 'date',
            'action_reference' => 'array',
        ];
    }

    public function panRequest(): BelongsTo
    {
        return $this->belongsTo(PanRequest::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * action_reference JSON → x-pan.prepared-details rows. Fixed fields get their
     * display labels; unknown fields (dynamic allowances) render their own name.
     */
    public function displayRows(): array
    {
        return array_map(fn (array $row) => [
            'label' => match ($row['field']) {
                'section' => 'Section',
                'place' => 'Place of Assignment',
                'head' => 'Immediate Head',
                'position' => 'Position',
                'joblevel' => 'Job Level',
                'basic' => 'Basic Pay',
                'leavecredits' => 'Leave Credits',
                default => $row['field'],
            },
            'from' => $row['field'] === 'basic' ? '₱ '.$row['from'] : $row['from'],
            'to' => $row['field'] === 'basic' ? '₱ '.$row['to'] : $row['to'],
            'chg' => $row['from'] !== $row['to'],
            'num' => $row['field'] === 'basic',
        ], $this->action_reference ?? []);
    }
}
