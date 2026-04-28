<?php

namespace App\Helpers;

class WhatsAppFormatter
{
    /**
     * Converte formatação WhatsApp para HTML seguro.
     * *negrito* → <strong>, _itálico_ → <em>, ~riscado~ → <del>, ```código``` → <code>
     */
    public static function format(?string $text): string
    {
        if (!$text) return '';

        // Escapa HTML para segurança (previne XSS)
        $text = e($text);

        // *negrito*
        $text = preg_replace('/\*([^\*]+)\*/', '<strong>$1</strong>', $text);

        // _itálico_
        $text = preg_replace('/\_([^\_]+)\_/', '<em>$1</em>', $text);

        // ~riscado~
        $text = preg_replace('/\~([^\~]+)\~/', '<del>$1</del>', $text);

        // ```código```
        $text = preg_replace('/```([^`]+)```/', '<code style="background:rgba(255,255,255,0.1); padding:2px 6px; border-radius:4px; font-family:monospace; font-size:12px;">$1</code>', $text);

        // `código inline`
        $text = preg_replace('/`([^`]+)`/', '<code style="background:rgba(255,255,255,0.1); padding:1px 4px; border-radius:3px; font-family:monospace; font-size:12px;">$1</code>', $text);

        // URLs clicáveis
        $text = preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener" style="color:#60a5fa; text-decoration:underline;">$1</a>',
            $text
        );

        return $text;
    }
}
