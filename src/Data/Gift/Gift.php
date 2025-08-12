<?php

namespace DonorPerfect\Data\Gift;

use DonorPerfect\Data\BaseData;

class Gift extends BaseData
{
    public function __construct(
        public string $giftId = '0',
        public string $donorId,
        public string $recordType = 'G',
        public string $giftDate,
        public float $amount,
        public ?string $glCode = null,
        public ?string $solicitCode = null,
        public ?string $subSolicitCode = null,
        public ?string $campaign = null,
        public ?string $giftType = null,
        public string $splitGift = 'N',
        public string $pledgePayment = 'N',
        public ?string $reference = null,
        public ?string $transactionId = null,
        public ?string $memoryHonor = null,
        public ?string $gfname = null,
        public ?string $glname = null,
        public float $fmv = 0.0,
        public int $batchNo = 0,
        public ?string $giftNarrative = null,
        public ?string $tyLetterNo = null,
        public ?string $glink = null,
        public ?string $plink = null,
        public string $nocalc = 'N',
        public string $receipt = 'N',
        public ?string $oldAmount = null,
        public ?string $userId = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function from(array $data): self
    {
        return new self(
            giftId: $data['gift_id'] ?? '0',
            donorId: $data['donor_id'] ?? '',
            recordType: $data['record_type'] ?? 'G',
            giftDate: $data['gift_date'] ?? '',
            amount: $data['amount'] ?? 0.0,
            glCode: $data['gl_code'] ?? null,
            solicitCode: $data['solicit_code'] ?? null,
            subSolicitCode: $data['sub_solicit_code'] ?? null,
            campaign: $data['campaign'] ?? null,
            giftType: $data['gift_type'] ?? null,
            splitGift: $data['split_gift'] ?? 'N',
            pledgePayment: $data['pledge_payment'] ?? 'N',
            reference: $data['reference'] ?? null,
            transactionId: $data['transaction_id'] ?? null,
            memoryHonor: $data['memory_honor'] ?? null,
            gfname: $data['gfname'] ?? null,
            glname: $data['glname'] ?? null,
            fmv: $data['fmv'] ?? 0.0,
            batchNo: $data['batch_no'] ?? 0,
            giftNarrative: $data['gift_narrative'] ?? null,
            tyLetterNo: $data['ty_letter_no'] ?? null,
            glink: $data['glink'] ?? null,
            plink: $data['plink'] ?? null,
            nocalc: $data['nocalc'] ?? 'N',
            receipt: $data['receipt'] ?? 'N',
            oldAmount: $data['old_amount'] ?? null,
            userId: $data['user_id'] ?? null,
        );
    }

    /**
     * Convert to array with proper parameter names for API
     */
    public function toApiArray(): array
    {
        return [
            'gift_id' => $this->giftId,
            'donor_id' => $this->donorId,
            'record_type' => $this->recordType,
            'gift_date' => $this->giftDate,
            'amount' => $this->amount,
            'gl_code' => $this->glCode,
            'solicit_code' => $this->solicitCode,
            'sub_solicit_code' => $this->subSolicitCode,
            'campaign' => $this->campaign,
            'gift_type' => $this->giftType,
            'split_gift' => $this->splitGift,
            'pledge_payment' => $this->pledgePayment,
            'reference' => $this->reference,
            'transaction_id' => $this->transactionId,
            'memory_honor' => $this->memoryHonor,
            'gfname' => $this->gfname,
            'glname' => $this->glname,
            'fmv' => $this->fmv,
            'batch_no' => $this->batchNo,
            'gift_narrative' => $this->giftNarrative,
            'ty_letter_no' => $this->tyLetterNo,
            'glink' => $this->glink,
            'plink' => $this->plink,
            'nocalc' => $this->nocalc,
            'receipt' => $this->receipt,
            'old_amount' => $this->oldAmount,
            'user_id' => $this->userId,
        ];
    }
}
