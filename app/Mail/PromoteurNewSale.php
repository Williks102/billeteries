namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class PromoteurNewSale extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💰 Nouvelle vente pour votre événement : ' . $this->order->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promoteur-new-sale',
        );
    }
}