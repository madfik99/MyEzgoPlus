<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingDetailsMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * @param array $data  // pass booking, customer, vehicle, flags, etc.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    // public function build()
    // {
    //     $subject = 'Booking Details – ' . ($this->data['agreement_no'] ?? '');

    //     return $this->subject($subject)
    //         ->markdown('reservation.emails.booking.details');
    // }

    public function build()
    {
        $subject = 'Booking Details';

        // return $this->subject($subject)
        //     ->from(config('mail.from.address'), config('mail.from.name'))
        //     ->view('reservation.emails.booking.details'); // HTML view below

        return $this->subject('Booking Details')
            ->view('reservation.emails.booking.details')  // not ->markdown()
            ->with(['data' => $this->data]);

    }

    
}
