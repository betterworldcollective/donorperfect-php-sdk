<?php

namespace DonorPerfect\Resources;

use DateTimeInterface;
use DonorPerfect\DonorPerfect;
use DonorPerfect\Requests\Flag\SaveFlag;
use Saloon\Http\BaseResource;

class FlagResource extends BaseResource
{
    /**
     * Apply a flag to a donor. Additive — never deletes existing flags.
     *
     * Use the additive `dp_saveflag_xml` action exclusively. The DP-side
     * `dp_delflags_xml` wipes ALL flags for a donor and is intentionally NOT
     * exposed by this SDK to prevent accidental data loss in sync listeners.
     *
     * @return array<string, mixed>
     */
    public function save(int $donorId, string $flagCode, ?DateTimeInterface $flagDate = null): array
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);

        $properties = [
            'matching_id' => $donorId,
            'flag' => $flagCode,
        ];

        if ($flagDate !== null) {
            $properties['flag_date'] = $flagDate->format('Y-m-d');
        }

        $response = $connector->send(new SaveFlag($properties));

        return $response->xmlArray();
    }
}
