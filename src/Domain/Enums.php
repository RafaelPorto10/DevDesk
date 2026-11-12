<?php
declare(strict_types=1);
namespace DevDesk\Domain;

enum Priority: string { case Baixa = 'baixa'; case Media = 'media'; case Alta = 'alta'; case Critica = 'critica'; }
enum Category: string { case Rede = 'rede'; case Hardware = 'hardware'; case Software = 'software'; case Acesso = 'acesso'; }

enum Status: string
{
    case Aberto = 'aberto';
    case EmAndamento = 'em_andamento';
    case Resolvido = 'resolvido';
    case Fechado = 'fechado';

    /** Máquina de estados: só transições válidas são permitidas. */
    public function canMoveTo(self $next): bool
    {
        return match ($this) {
            self::Aberto      => $next === self::EmAndamento,
            self::EmAndamento => in_array($next, [self::Resolvido, self::Aberto], true),
            self::Resolvido   => in_array($next, [self::Fechado, self::EmAndamento], true),
            self::Fechado     => false,
        };
    }
}
