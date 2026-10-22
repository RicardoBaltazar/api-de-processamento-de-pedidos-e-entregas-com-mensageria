<?php

namespace App\Enums;

enum OrderStatus: string
{
    case CRIADO = 'CRIADO';
    case AGUARDANDO_PAGAMENTO = 'AGUARDANDO_PAGAMENTO';
    case PAGO = 'PAGO';
    case EM_PREPARACAO = 'EM_PREPARACAO';
    case ENVIADO = 'ENVIADO';
    case ENTREGUE = 'ENTREGUE';
    case CANCELADO = 'CANCELADO';

    public function next(): ?self
    {
        return match ($this) {
            self::CRIADO => self::AGUARDANDO_PAGAMENTO,
            self::AGUARDANDO_PAGAMENTO => self::PAGO,
            self::PAGO => self::EM_PREPARACAO,
            self::EM_PREPARACAO => self::ENVIADO,
            self::ENVIADO => self::ENTREGUE,
            self::ENTREGUE, self::CANCELADO => null,
        };
    }

    public function canBeCancelled(): bool
    {
        return ! in_array($this, [self::ENTREGUE, self::CANCELADO], true);
    }

    public function requiresAdminToCancel(): bool
    {
        return in_array($this, [self::PAGO, self::EM_PREPARACAO, self::ENVIADO], true);
    }
}
