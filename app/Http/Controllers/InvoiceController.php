<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = $this->ownedInvoices($request)
            ->with('items')
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('client_name', 'like', "%{$search}%")
                        ->orWhere('period', 'like', "%{$search}%");
                });
            })
            ->latest('issue_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'invoiceCount' => $this->ownedInvoices($request)->count(),
            'monthCount' => $this->ownedInvoices($request)
                ->whereBetween('issue_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
            'totalAmount' => InvoiceItem::query()
                ->whereHas('invoice', fn (Builder $query) => $query->where('user_id', $request->user()->id))
                ->sum('amount'),
        ]);
    }

    public function create(): View
    {
        $invoice = new Invoice([
            'invoice_number' => 'FAC-'.now()->format('Ym').'-'.Str::upper(Str::random(5)),
            'issue_date' => today(),
            'period' => 'Prestation '.now()->locale('fr')->translatedFormat('F Y'),
            'provider_name' => auth()->user()->name,
            'provider_title' => '',
            'provider_address' => '',
            'provider_tax_id' => 'en cours',
            'payment_method' => 'Virement bancaire',
        ]);
        $rows = [
            ['description' => '', 'details' => '', 'quantity' => '1', 'unit' => 'jour', 'amount' => ''],
        ];

        return view('invoices.form', compact('invoice', 'rows'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedInvoice($request);
        $items = $data['items'];
        unset($data['items']);
        $data['user_id'] = $request->user()->id;

        $invoice = DB::transaction(function () use ($data, $items): Invoice {
            $invoice = Invoice::create($data);
            $invoice->items()->createMany($items);

            return $invoice;
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'La facture a été créée.');
    }

    public function show(Request $request, int $invoice): View
    {
        return view('invoices.show', [
            'invoice' => $this->findOwnedInvoice($request, $invoice),
        ]);
    }

    public function edit(Request $request, int $invoice): View
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);

        return view('invoices.form', [
            'invoice' => $invoice,
            'rows' => $invoice->items->toArray(),
        ]);
    }

    public function update(Request $request, int $invoice): RedirectResponse
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);
        $data = $this->validatedInvoice($request, $invoice);
        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($invoice, $data, $items): void {
            $invoice->update($data);
            $invoice->items()->delete();
            $invoice->items()->createMany($items);
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('status', 'La facture a été mise à jour.');
    }

    public function destroy(Request $request, int $invoice): RedirectResponse
    {
        $this->findOwnedInvoice($request, $invoice)->delete();

        return redirect()
            ->route('invoices.index')
            ->with('status', 'La facture a été supprimée.');
    }

    public function pdf(Request $request, int $invoice): Response
    {
        $invoice = $this->findOwnedInvoice($request, $invoice);
        $filename = Str::slug($invoice->invoice_number).'.pdf';

        return Pdf::loadView('invoices.pdf', compact('invoice'))
            ->setPaper('a4')
            ->download($filename);
    }

    private function validatedInvoice(Request $request, ?Invoice $invoice = null): array
    {
        $numberRule = Rule::unique('invoices', 'invoice_number')
            ->where('user_id', $request->user()->id);

        if ($invoice !== null) {
            $numberRule->ignore($invoice->id);
        }

        return $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', $numberRule],
            'issue_date' => ['required', 'date'],
            'period' => ['required', 'string', 'max:120'],
            'provider_name' => ['required', 'string', 'max:150'],
            'provider_title' => ['required', 'string', 'max:120'],
            'provider_address' => ['required', 'string', 'max:255'],
            'provider_phone' => ['nullable', 'string', 'max:60'],
            'provider_email' => ['nullable', 'email', 'max:150'],
            'provider_tax_id' => ['nullable', 'string', 'max:100'],
            'client_name' => ['required', 'string', 'max:150'],
            'client_contact' => ['nullable', 'string', 'max:150'],
            'client_address' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.description' => ['required', 'string', 'max:180'],
            'items.*.details' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'items.*.unit' => ['required', 'string', 'max:40'],
            'items.*.amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ], [
            'items.required' => 'Ajoutez au moins une prestation.',
            'items.min' => 'Ajoutez au moins une prestation.',
            'invoice_number.unique' => 'Ce numéro de facture existe déjà dans votre espace.',
        ]);
    }

    private function findOwnedInvoice(Request $request, int $id): Invoice
    {
        return $this->ownedInvoices($request)
            ->with('items')
            ->findOrFail($id);
    }

    private function ownedInvoices(Request $request): Builder
    {
        return Invoice::query()->where('user_id', $request->user()->id);
    }
}
