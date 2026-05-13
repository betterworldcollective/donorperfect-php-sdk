<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\DonorPerfectException;
use DonorPerfect\Requests\Flag\SaveFlag;
use Saloon\Http\BaseResource;
use SimpleXMLElement;

class FlagResource extends BaseResource
{
    /**
     * Apply a flag to a donor. Additive — never deletes existing flags.
     *
     * Per DP XML API docs (dp_saveflag_xml, p.48): three required params —
     * @donor_id, @flag, @user_id. Note: NOT @matching_id (that's dp_save_udf_xml).
     * The complementary `dp_delflags_xml` (wipes ALL flags) is intentionally NOT exposed.
     *
     * @return array<string, mixed>
     *
     * @throws DonorPerfectException when DP returns an empty or malformed response
     */
    public function save(int $donorId, string $flagCode, string $userId): array
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $response = $connector->send(new SaveFlag([
            'donor_id' => $donorId,
            'flag' => $flagCode,
            'user_id' => $userId,
        ]));

        $body = $response->body();
        $xml = $response->xml();

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->record)) {
            throw new DonorPerfectException('DonorPerfect rejected SaveFlag: '.$body);
        }

        return $response->xmlArray();
    }
}
