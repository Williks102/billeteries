namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use App\Models\Order;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre commande #' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'customer' => $this->order->user,
                'event' => $this->order->event,
                'total' => number_format($this->order->total_amount) . ' FCFA'
            ]
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        
        // Attacher le PDF des billets si la commande est payée
        if ($this->order->payment_status === 'paid') {
            try {
                $pdf = \PDF::loadView('acheteur.tickets-pdf', ['order' => $this->order]);
                $pdfContent = $pdf->output();
                
                $attachments[] = Attachment::fromData(
                    fn() => $pdfContent,
                    'billets-' . $this->order->order_number . '.pdf'
                )->withMime('application/pdf');
            } catch (\Exception $e) {
                \Log::error('Erreur génération PDF email: ' . $e->getMessage());
            }
        }
        
        return $attachments;
    }
}