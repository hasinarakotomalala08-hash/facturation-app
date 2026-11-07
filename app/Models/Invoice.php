<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'invoice_number',
    'issue_date',
    'period',
    'provider_name',
    'provider_title',
    'provider_address',
    'provider_phone',
    'provider_email',
    'provider_tax_id',
    'client_name',
    'client_contact',
    'client_address',
    'payment_method',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getTotalAttribute(): int
    {
        return (int) $this->items->sum('amount');
    }

    public function getBilledDaysAttribute(): float
    {
        return (float) $this->items
            ->filter(fn (InvoiceItem $item): bool => in_array(mb_strtolower(trim($item->unit)), ['jour', 'jours'], true))
            ->sum('quantity');
    }
}
