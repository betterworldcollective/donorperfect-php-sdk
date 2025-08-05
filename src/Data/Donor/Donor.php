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

    public static function from(array $data): Donor
    {
        return new self(
            donorId: $data['donor_id'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            email: $data['email'] ?? null,
            homePhone: $data['home_phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            orgRec: $data['org_rec'] ?? 'N',
            organization: $data['organization'] ?? null,
            donorType: DonorType::from($data['donor_type'] ?? 'IN'),
            userId: $data['user_id'] ?? null,
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
