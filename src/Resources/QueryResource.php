<?php

namespace DonorPerfect\Resources;

use DonorPerfect\DonorPerfect;
use Saloon\Http\BaseResource;
use Saloon\Http\Request;

class QueryResource extends BaseResource
{
    public function __construct(
        protected DonorPerfect $connector
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function queryTable(string $tableName, array $filters = []): array
    {
        $request = new class($tableName, $filters) extends Request
        {
            public function __construct(
                protected string $tableName,
                protected array $filters
            ) {}

            public function resolveEndpoint(): string
            {
                return '/sql';
            }

            protected function defaultBody(): string
            {
                $sql = "SELECT * FROM {$this->tableName}";
                if (! empty($this->filters)) {
                    $sql .= ' WHERE '.implode(' AND ', $this->filters);
                }

                return $sql;
            }

            protected function defaultHeaders(): array
            {
                return [
                    'Content-Type' => 'text/plain',
                ];
            }
        };

        $response = $this->connector->send($request);
        $result = $response->xmlArray();
        
        if (!is_array($result)) {
            return [];
        }

        /** @var array<string, mixed> $result */
        return $result;
    }
}
