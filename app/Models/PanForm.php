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
        // Money rows = Basic Pay AND every dynamic allowance (any non-fixed field).
        $money = fn (string $field) => ! in_array($field, ['section', 'place', 'head', 'position', 'joblevel', 'leavecredits'], true);
        $peso = fn (string $value) => in_array($value, ['', '—'], true) ? '—' : '₱ '.$value;

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
            'from' => $money($row['field']) ? $peso($row['from']) : $row['from'],
            'to' => $money($row['field']) ? $peso($row['to']) : $row['to'],
            // Highlights any value HR actually entered — not just ones that changed —
            // so a legitimately-unchanged field (e.g. Position left as-is) still reads
            // as "confirmed," instead of looking indistinguishable from a blank one.
            'chg' => filled($row['to']),
            'num' => $money($row['field']),
        ], $this->action_reference ?? []);
    }
}
