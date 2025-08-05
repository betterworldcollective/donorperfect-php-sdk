<?php

namespace DonorPerfect\Data\Gift;

use DonorPerfect\Data\BaseData;
use DonorPerfect\Enums\GiftType;
use DonorPerfect\Enums\PaymentType;

class Gift extends BaseData
{
    public function __construct(
        public string $giftId,
        public string $donorId,
        public string $giftDate,
        public float $amount,
        public GiftType $giftType,
        public PaymentType $paymentType,
        public ?string $reference = null,
        public ?string $externalId = null,
        public ?string $notes = null,
        public ?string $userId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from(array $data): self
    {
        return new self(
            giftId: (string) ($data['gift_id'] ?? ''),
            donorId: (string) ($data['donor_id'] ?? ''),
            giftDate: (string) ($data['gift_date'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            giftType: GiftType::from((string) ($data['gift_type'] ?? 'G')),
            paymentType: PaymentType::from((string) ($data['payment_type'] ?? 'CC')),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            externalId: isset($data['external_id']) ? (string) $data['external_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
        );
    }
}
