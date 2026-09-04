<?php

namespace Modules\Crm\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Proposal => 'Proposal',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function canTransitionTo(self $to): bool
    {
        if ($this === $to) {
            return false;
        }

        return match ($this) {
            self::New => in_array($to, [self::Contacted, self::Qualified, self::Lost], true),
            self::Contacted => in_array($to, [self::Qualified, self::Proposal, self::Lost], true),
            self::Qualified => in_array($to, [self::Proposal, self::Won, self::Lost], true),
            self::Proposal => in_array($to, [self::Won, self::Lost, self::Qualified], true),
            self::Won, self::Lost => false,
        };
    }

    public function canConvert(): bool
    {
        return in_array($this, [self::Qualified, self::Proposal, self::Won], true);
    }

    /**
     * @return list<self>
     */
    public static function pipelineStages(): array
    {
        return [
            self::New,
            self::Contacted,
            self::Qualified,
            self::Proposal,
            self::Won,
        ];
    }
}
