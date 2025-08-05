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
            giftId: $data['gift_id'] ?? '',
            donorId: $data['donor_id'] ?? '',
            giftDate: $data['gift_date'] ?? '',
            amount: $data['amount'] ?? 0.0,
            giftType: GiftType::from($data['gift_type'] ?? 'G'),
            paymentType: PaymentType::from($data['payment_type'] ?? 'CC'),
            reference: $data['reference'] ?? null,
            externalId: $data['external_id'] ?? null,
            notes: $data['notes'] ?? null,
            userId: $data['user_id'] ?? null,
        );
    }
}
