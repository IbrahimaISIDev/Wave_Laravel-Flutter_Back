<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WelcomeUser extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $qrUrl;
    protected $cardPdfPath;
    public $code;

    public function __construct($user, $qrUrl, $cardPdfPath = null, $code = null)
    {
        $this->user = $user;
        $this->qrUrl = $qrUrl;
        $this->cardPdfPath = $cardPdfPath;
        $this->code = $code;
    }

    public function build()
    {
        $mail = $this->view('emails.welcome')
                     ->subject('Bienvenue sur SamaXaalis - Votre nouvelle expérience de transfert d\'argent')
                     ->with([
                         'userName' => $this->user->name, // Ajouter le prénom ou nom de l'utilisateur
                         'qrUrl' => $this->qrUrl,
                         'code' => $this->code,
                     ]);

        // Si une carte PDF est fournie, l'attacher
        if ($this->cardPdfPath && file_exists($this->cardPdfPath)) {
            $mail->attach($this->cardPdfPath, [
                'as' => 'votre_carte_samaxaalis.pdf',
                'mime' => 'application/pdf'
            ]);
        }

        return $mail;
    }
}
