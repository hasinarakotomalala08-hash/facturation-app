<?php

namespace Tests\Feature;

use App\Mail\SendInvoiceMail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_create_and_export_an_invoice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('invoices.store'), $this->invoiceData([
                'client_email' => 'client@example.test',
            ]))
            ->assertRedirect();

        $invoice = Invoice::with('items')->firstOrFail();

        $this->assertSame('client@example.test', $invoice->client_email);
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

    public function test_invoice_creation_rejects_an_invalid_client_email(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('invoices.create'))
            ->post(route('invoices.store'), $this->invoiceData([
                'client_email' => 'not-an-email',
            ]))
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors('client_email');

        $this->assertDatabaseCount('invoices', 0);
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

    public function test_an_invoice_can_be_queued_to_its_client_with_a_pdf_attachment(): void
    {
        $this->travelTo('2026-11-15 10:00:00');
        $user = User::factory()->create();
        $invoice = $user->invoices()->create(array_merge($this->invoiceAttributes(), [
            'client_email' => 'client@example.test',
        ]));
        $invoice->items()->create([
            'description' => 'Prestation de conseil',
            'quantity' => 1,
            'unit' => 'forfait',
            'amount' => 100_000,
        ]);
        Mail::fake();

        $this->actingAs($user)
            ->post(route('invoices.send', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('status', 'La facture a été placée dans la file d’envoi.');

        $message = 'La facture a été placée dans la file d’envoi.';
        $response = $this->actingAs($user)
            ->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSeeText($message);
        $this->assertSame(1, substr_count($response->getContent(), $message));

        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        Mail::assertQueued(SendInvoiceMail::class, function (SendInvoiceMail $mail) use ($invoice): bool {
            $renderedBody = $mail->render();
            $attachments = $mail->attachments();

            return $mail->hasTo('client@example.test')
                && $mail->hasFrom('prestataire@example.test')
                && $mail->from[0]['name'] === null
                && $mail->invoice->id === $invoice->id
                && $mail->hasSubject('[facture] Octobre - 2026')
                && $renderedBody === ''
                && count($attachments) === 1
                && $attachments[0]->as === "facture-{$invoice->invoice_number}.pdf"
                && $attachments[0]->mime === 'application/pdf';
        });
    }

    public function test_invoice_list_displays_email_sent_and_draft_statuses(): void
    {
        $user = User::factory()->create();
        $user->invoices()->create(array_merge($this->invoiceAttributes(), [
            'status' => 'sent',
            'sent_at' => now(),
        ]));
        $user->invoices()->create(array_merge($this->invoiceAttributes(), [
            'invoice_number' => 'FAC-202611-FGHIJ',
        ]));

        $this->actingAs($user)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSeeText('Envoyée par e-mail')
            ->assertSeeText('Brouillon');
    }

    public function test_invoice_email_subject_uses_previous_year_in_january(): void
    {
        $this->travelTo('2026-01-15 10:00:00');
        $user = User::factory()->create();
        $invoice = $user->invoices()->create($this->invoiceAttributes());

        $mail = new SendInvoiceMail($invoice);

        $this->assertSame('[facture] Décembre - 2025', $mail->envelope()->subject);
    }

    public function test_invoice_send_requires_a_client_email(): void
    {
        $user = User::factory()->create();
        $invoice = $user->invoices()->create($this->invoiceAttributes());
        Mail::fake();

        $this->actingAs($user)
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.send', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHasErrors([
                'client_email' => "Ajoutez l'adresse e-mail du client avant d'envoyer la facture.",
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
            'sent_at' => null,
        ]);
        Mail::assertNothingQueued();
    }

    public function test_invoice_send_requires_a_provider_email_address(): void
    {
        $user = User::factory()->create();
        $invoice = $user->invoices()->create(array_merge($this->invoiceAttributes(), [
            'client_email' => 'client@example.test',
            'provider_email' => null,
        ]));
        Mail::fake();

        $this->actingAs($user)
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.send', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHasErrors([
                'provider_email' => "Ajoutez l'adresse e-mail du prestataire avant d'envoyer la facture.",
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'draft',
            'sent_at' => null,
        ]);
        Mail::assertNothingQueued();
    }

    public function test_a_user_cannot_send_another_users_invoice(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $invoice = $owner->invoices()->create(array_merge($this->invoiceAttributes(), [
            'client_email' => 'client@example.test',
        ]));
        Mail::fake();

        $this->actingAs($otherUser)
            ->post(route('invoices.send', $invoice))
            ->assertNotFound();

        Mail::assertNothingQueued();
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
        $invoice = User::factory()->create()->invoices()->create($this->invoiceAttributes());

        $this->get(route('invoices.index'))->assertRedirect(route('login'));
        $this->post(route('invoices.send', $invoice))->assertRedirect(route('login'));
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
