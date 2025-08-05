<?php

namespace DonorPerfect\Resources;

use DonorPerfect\Data\Gift\Gift;
use DonorPerfect\DonorPerfect;
use DonorPerfect\Exceptions\BadRequestException;
use DonorPerfect\Exceptions\InvalidDataException;
use DonorPerfect\Exceptions\ObjectNotFoundException;
use DonorPerfect\Exceptions\UnauthorizedException;
use Saloon\Http\BaseResource;

class GiftResource extends BaseResource
{
    /**
     * @throws ObjectNotFoundException
     * @throws UnauthorizedException
     * @throws InvalidDataException
     */
    public function get(int $id): Gift
    {
        // Use SQL query to get gift (matches real DonorPerfect API)
        $sql = "SELECT * FROM dp_gifts WHERE gift_id = {$id}";
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        $result = $connector->callSql($sql);

        if (empty($result)) {
            throw new ObjectNotFoundException;
        }

        return Gift::from($result[0]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return int The ID of the newly created gift returned by the API response
     *
     * @throws BadRequestException
     * @throws UnauthorizedException
     *
     * @see https://api.donorperfect.net/docs/gift-api List of available properties
     */
    public function create(array $properties): int
    {
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        return $connector->saveGift($properties);
    }

    /**
     * @param  array<string, mixed>  $properties
     *
     * @throws BadRequestException
     * @throws UnauthorizedException
     *
     * @see https://api.donorperfect.net/docs/gift-api List of available properties
     */
    public function update(int $id, array $properties): true
    {
        // For DonorPerfect, update is the same as create with gift_id
        $properties['gift_id'] = $id;
        $connector = $this->connector;
        assert($connector instanceof DonorPerfect);
        $connector->saveGift($properties);

        return true;
    }
}
