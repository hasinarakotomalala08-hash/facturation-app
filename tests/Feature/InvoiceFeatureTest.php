<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_and_export_an_invoice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('invoices.store'), $this->invoiceData())
            ->assertRedirect();

        $invoice = Invoice::with('items')->firstOrFail();

        $this->assertSame(1_010_400, $invoice->total);
        $this->assertSame(21.0, $invoice->billed_days);
        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Prestation d’assistant virtuel',
            'amount' => 1_010_400,
        ]);

        $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Services réalisés au cours du mois')
            ->assertSee('1 010 400')
            ->assertSee('21&nbsp;jours', false);

        $pdfHtml = view('invoices.pdf', compact('invoice'))->render();
        $this->assertStringContainsString('display: table;', $pdfHtml);
        $this->assertStringContainsString('@page { margin: 0; }', $pdfHtml);
        $this->assertStringContainsString('<body class="pdf-document">', $pdfHtml);
        $this->assertStringContainsString('body.pdf-document .invoice-paper', $pdfHtml);
        $this->assertStringContainsString('padding: 49px 50px 38px;', $pdfHtml);
        $this->assertStringContainsString('Services réalisés au cours du mois', $pdfHtml);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_invoices_are_private_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $invoice = $owner->invoices()->create($this->invoiceAttributes());

        $this->actingAs($otherUser)
            ->get(route('invoices.show', $invoice))
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->get(route('invoices.pdf', $invoice))
            ->assertNotFound();
    }

    public function test_an_invoice_can_be_updated_with_multiple_items(): void
    {
        $user = User::factory()->create();
        $invoice = $user->invoices()->create($this->invoiceAttributes());
        $invoice->items()->create([
            'description' => 'Ancienne prestation',
            'quantity' => 1,
            'unit' => 'jour',
            'amount' => 100,
        ]);
        $payload = $this->invoiceData([
            'invoice_number' => $invoice->invoice_number,
            'items' => [
                ['description' => 'Conseil', 'details' => 'Session mensuelle', 'quantity' => 2, 'unit' => 'heure', 'amount' => 80_000],
                ['description' => 'Suivi', 'details' => '', 'quantity' => 1, 'unit' => 'forfait', 'amount' => 20_000],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Modifier la facture');

        $this->actingAs($user)
            ->put(route('invoices.update', $invoice), $payload)
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh()->load('items');
        $this->assertSame(100_000, $invoice->total);
        $this->assertCount(2, $invoice->items);
    }

    public function test_invoice_pages_require_an_account(): void
    {
        $this->get(route('invoices.index'))->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_open_the_invoice_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('Créer une facture')
            ->assertSee('items[0][description]');
    }

    private function invoiceData(array $overrides = []): array
    {
        return array_replace_recursive($this->invoiceAttributes(), [
            'items' => [
                [
                    'description' => 'Prestation d’assistant virtuel',
                    'details' => 'Services réalisés au cours du mois',
                    'quantity' => 21,
                    'unit' => 'jours',
                    'amount' => 1_010_400,
                ],
            ],
        ], $overrides);
    }

    private function invoiceAttributes(): array
    {
        return [
            'invoice_number' => 'FAC-202611-ABCDE',
            'issue_date' => '2026-11-03',
            'period' => 'Prestation octobre 2026',
            'provider_name' => 'Prestataire Test',
            'provider_title' => 'Consultant',
            'provider_address' => 'Antananarivo',
            'provider_phone' => '0340000000',
            'provider_email' => 'prestataire@example.test',
            'provider_tax_id' => 'NIF-123',
            'client_name' => 'Client Test',
            'client_contact' => 'Responsable Test',
            'client_address' => 'Antananarivo',
            'payment_method' => 'Virement bancaire',
        ];
    }
}
