<?php

namespace DonorPerfect\Data\Donor;

use DonorPerfect\Data\BaseData;
use DonorPerfect\Enums\DonorType;

class Donor extends BaseData
{
    public function __construct(
        public string $donorId,
        public string $firstName,
        public string $lastName,
        public ?string $email = null,
        public ?string $homePhone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip = null,
        public ?string $country = null,
        public string $orgRec = 'N',
        public ?string $organization = null,
        public DonorType $donorType = DonorType::Individual,
        public ?string $userId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from(array $data): self
    {
        return new self(
            donorId: (string) ($data['donor_id'] ?? ''),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
            email: isset($data['email']) ? (string) $data['email'] : null,
            homePhone: isset($data['home_phone']) ? (string) $data['home_phone'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            state: isset($data['state']) ? (string) $data['state'] : null,
            zip: isset($data['zip']) ? (string) $data['zip'] : null,
            country: isset($data['country']) ? (string) $data['country'] : null,
            orgRec: (string) ($data['org_rec'] ?? 'N'),
            organization: isset($data['organization']) ? (string) $data['organization'] : null,
            donorType: DonorType::from((string) ($data['donor_type'] ?? 'IN')),
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
        );
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function isOrganization(): bool
    {
        return $this->orgRec === 'Y';
    }
}
