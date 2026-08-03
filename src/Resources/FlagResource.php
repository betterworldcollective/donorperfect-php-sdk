<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\CallSqlRequest;
use DonorPerfect\Requests\Flag\SaveFlag;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class FlagResource extends BaseResource
{
    /**
     * Flag codes already applied to a donor, upper-cased.
     *
     * Callers need this to keep save() idempotent — `dpflags` is keyed on
     * (donor_id, flag) under a case-insensitive collation, so re-applying an existing
     * flag is a primary-key violation.
     *
     * @return list<string>
     *
     * @throws DonorPerfectException
     */
    public function listForDonor(int $donorId): array
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $response = $connector->send(new CallSqlRequest("SELECT flag FROM dpflags WHERE donor_id = {$donorId}"));
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $codes = [];

        foreach ($xml->record as $record) {
            $code = strtoupper(trim((string) ($record->field['value'] ?? '')));

            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Apply a flag to a donor. Additive — never deletes existing flags.
     *
     * Per DP XML API docs (dp_saveflag_xml, p.48) the three required params are
     * `donor_id`, `flag` and `user_id` — note `donor_id`, NOT `matching_id` (that one
     * belongs to dp_save_udf_xml). The complementary `dp_delflags_xml` (which wipes
     * ALL of a donor's flags) is intentionally NOT exposed.
     *
     * NOT idempotent — filter against listForDonor() first. A duplicate is rejected as
     * a SQL failure that DP disguises as "user not authorized for this api call."
     *
     * @return array<string, mixed>
     *
     * @throws DonorPerfectException when DP rejects the call
     */
    public function save(int $donorId, string $flagCode, string $userId): array
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        return $connector->send(new SaveFlag([
            'donor_id' => $donorId,
            'flag' => $flagCode,
            'user_id' => $userId,
        ]))->xmlArray();
    }
}
