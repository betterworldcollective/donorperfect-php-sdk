<?php

namespace DonorPerfect\Resources;

use DonorPerfect\Data\Donor\Donor;
use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\BadRequestException;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Exceptions\ObjectNotFoundException;
use DonorPerfect\Exceptions\UnauthorizedException;
use Saloon\Http\BaseResource;

class DonorResource extends BaseResource
{
    /**
     * @throws ObjectNotFoundException
     * @throws UnauthorizedException
     * @throws InvalidDataException
     */
    public function get(int $id): Donor
    {
        // Use SQL query to get donor (matches real DonorPerfect API)
        $sql = "SELECT * FROM dp WHERE donor_id = {$id}";
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        $result = $connector->callSql($sql);

        if (empty($result)) {
            throw new ObjectNotFoundException;
        }

        return Donor::from($result[0]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return int The ID of the newly created donor returned by the API response
     *
     * @throws BadRequestException
     * @throws UnauthorizedException
     *
     * @see https://api.donorperfect.net/docs/donor-api List of available properties
     */
    public function create(array $properties): int
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        return $connector->saveDonor($properties);
    }

    /**
     * @param  array<string, mixed>  $properties
     *
     * @throws BadRequestException
     * @throws UnauthorizedException
     *
     * @see https://api.donorperfect.net/docs/donor-api List of available properties
     */
    public function update(int $id, array $properties): true
    {
        // For DonorPerfect, update is the same as create with donor_id
        $properties['donor_id'] = $id;
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        $connector->saveDonor($properties);

        return true;
    }
}
