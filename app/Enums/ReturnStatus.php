<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Where a return or exchange has got to.
 *
 * The lifecycle is a line with one branch:
 *
 *     requested ──> approved ──> received ──> completed
 *         └───────> rejected
 *
 * `approved` and `received` are deliberately separate. Approving is a promise
 * to accept the parcel; receiving is the parcel actually arriving. Only the
 * second is a fact about inventory, which is why it — not approval — is what
 * puts stock back and sends a replacement out.
 */
enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Received = 'received';
    case Completed = 'completed';

    public function label(): string
    {
        return __('returns.status.'.$this->value);
    }

    public function badge(): string
    {
        return match ($this) {
            self::Requested => 'neutral',
            self::Approved  => 'denim',
            self::Received  => 'gold',
            self::Completed => 'success',
            self::Rejected  => 'danger',
        };
    }

    /**
     * Where this request may go next.
     *
     * Returned as a graph rather than assumed to be a line, so the rules live
     * in one place instead of being re-derived by each caller.
     *
     * @return list<self>
     */
    public function nextStates(): array
    {
        return match ($this) {
            self::Requested => [self::Approved, self::Rejected],

            // An approved return can still be refused if what arrives is not
            // what was described — worn, washed, or a different piece entirely.
            self::Approved  => [self::Received, self::Rejected],

            self::Received  => [self::Completed],

            self::Rejected, self::Completed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->nextStates(), strict: true);
    }

    public function isFinal(): bool
    {
        return $this->nextStates() === [];
    }

    /**
     * Whether staff have already answered.
     */
    public function isDecided(): bool
    {
        return $this !== self::Requested;
    }

    /**
     * Whether the customer may still withdraw it.
     *
     * Only before anyone has acted: once approved, the shop has committed and
     * may already have set a replacement aside.
     */
    public function isCancellable(): bool
    {
        return $this === self::Requested;
    }

    /**
     * Whether reaching this status means the goods are physically back with us.
     *
     * The inventory boundary: crossing it is what moves stock, and it is
     * checked rather than each caller listing the statuses itself.
     */
    public function goodsInHand(): bool
    {
        return in_array($this, [self::Received, self::Completed], strict: true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
