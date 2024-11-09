<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Mail\WelcomeUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification
{
    public function handle(UserRegistered $event)
    {
        try {
            // Envoi du mail de bienvenue
            Mail::to($event->mailData['user']->email)
                ->send(new WelcomeUser(
                    $event->mailData['user'],
                    $event->mailData['qrUrl'],
                    $event->mailData['cardPdfPath'],
                    $event->mailData['code']
                ));

            // Log pour l'envoi du mail
            Log::info('Un e-mail de bienvenue a été envoyé avec succès à : ' . $event->mailData['user']->email 
                      . ' pour l\'application SamaXaalis.');
        } catch (\Exception $e) {
            // Log en cas d'erreur
            Log::error('Erreur lors de l\'envoi de l\'email de bienvenue pour SamaXaalis : ' . $e->getMessage());
        }
    }
}
