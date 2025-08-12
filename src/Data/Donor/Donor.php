<?php

namespace DonorPerfect\Data\Donor;

use DonorPerfect\Data\BaseData;
use DonorPerfect\Enums\DonorType;

class Donor extends BaseData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $donorId = '0',
        public ?string $middleName = null,
        public ?string $suffix = null,
        public ?string $title = null,
        public ?string $salutation = null,
        public ?string $profTitle = null,
        public ?string $optLine = null,
        public ?string $address = null,
        public ?string $address2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip = null,
        public ?string $country = null,
        public ?string $addressType = null,
        public ?string $homePhone = null,
        public ?string $businessPhone = null,
        public ?string $faxPhone = null,
        public ?string $mobilePhone = null,
        public ?string $email = null,
        public string $orgRec = 'N',
        public DonorType $donorType = DonorType::Individual,
        public string $nomail = 'N',
        public ?string $nomailReason = null,
        public ?string $narrative = null,
        public string $donorRcptType = 'I',
        public ?string $userId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from(array $data): self
    {
        return new self(
            firstName: $data['first_name'] ?? '',
            lastName: $data['last_name'] ?? '',
            donorId: $data['donor_id'] ?? '0',
            middleName: $data['middle_name'] ?? null,
            suffix: $data['suffix'] ?? null,
            title: $data['title'] ?? null,
            salutation: $data['salutation'] ?? null,
            profTitle: $data['prof_title'] ?? null,
            optLine: $data['opt_line'] ?? null,
            address: $data['address'] ?? null,
            address2: $data['address2'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            zip: $data['zip'] ?? null,
            country: $data['country'] ?? null,
            addressType: $data['address_type'] ?? null,
            homePhone: $data['home_phone'] ?? null,
            businessPhone: $data['business_phone'] ?? null,
            faxPhone: $data['fax_phone'] ?? null,
            mobilePhone: $data['mobile_phone'] ?? null,
            email: $data['email'] ?? null,
            orgRec: $data['org_rec'] ?? 'N',
            donorType: DonorType::from($data['donor_type'] ?? 'IN'),
            nomail: $data['nomail'] ?? 'N',
            nomailReason: $data['nomail_reason'] ?? null,
            narrative: $data['narrative'] ?? null,
            donorRcptType: $data['donor_rcpt_type'] ?? 'I',
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

    /**
     * Convert to array with proper parameter names for API
     */
    public function toApiArray(): array
    {
        return [
            'donor_id' => $this->donorId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'suffix' => $this->suffix,
            'title' => $this->title,
            'salutation' => $this->salutation,
            'prof_title' => $this->profTitle,
            'code_edit',
            'opt_line' => $this->optLine,
            'address' => $this->address,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'address_type' => $this->addressType,
            'home_phone' => $this->homePhone,
            'business_phone' => $this->businessPhone,
            'fax_phone' => $this->faxPhone,
            'mobile_phone' => $this->mobilePhone,
            'email' => $this->email,
            'org_rec' => $this->orgRec,
            'donor_type' => $this->donorType->value,
            'nomail' => $this->nomail,
            'nomail_reason' => $this->nomailReason,
            'narrative' => $this->narrative,
            'donor_rcpt_type' => $this->donorRcptType,
            'user_id' => $this->userId,
        ];
    }
}
