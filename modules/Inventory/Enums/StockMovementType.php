<?php

namespace Modules\Inventory\Enums;

enum StockMovementType: string
{
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case PurchaseReceive = 'purchase_receive';
    case SaleFulfill = 'sale_fulfill';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';
    case Damage = 'damage';

    public function label(): string
    {
        return match ($this) {
            self::AdjustmentIn => 'Adjustment In',
            self::AdjustmentOut => 'Adjustment Out',
            self::PurchaseReceive => 'Purchase Receive',
            self::SaleFulfill => 'Sale Fulfill',
            self::TransferIn => 'Transfer In',
            self::TransferOut => 'Transfer Out',
            self::ReturnIn => 'Return In',
            self::ReturnOut => 'Return Out',
            self::Damage => 'Damage',
        };
    }

    public function isInbound(): bool
    {
        return in_array($this, [
            self::AdjustmentIn,
            self::PurchaseReceive,
            self::TransferIn,
            self::ReturnIn,
        ], true);
    }

    public function signedQuantity(float|string $quantity): string
    {
        $qty = abs((float) $quantity);

        return $this->isInbound()
            ? number_format($qty, 4, '.', '')
            : number_format(-$qty, 4, '.', '');
    }
}
