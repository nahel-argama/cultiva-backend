<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Clients;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Config;
use Cultiva\Integrations\GoLog\DTO\GoLogAuthResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogDriverResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogEquipamentGroupResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTractorResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogUserResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleStatus;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

class GoLogClient
{
    private const string CACHE_KEY = 'golog_auth_session';

    public function __construct(
        private readonly Config $config,
        private readonly HttpFactory $http,
    ) {}

    public function getAuthSession(): GoLogAuthResponseDTO
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return GoLogAuthResponseDTO::fromArray($cached);
        }

        return $this->login();
    }

    public function getToken(): string
    {
        return $this->getAuthSession()->token;
    }

    public function getCompanyId(): string
    {
        return $this->getAuthSession()->companyId;
    }

    public function login(): GoLogAuthResponseDTO
    {
        $response = $this->http->baseUrl($this->config->getBaseUrl())
            ->timeout($this->config->getTimeout())
            ->acceptJson()
            ->post('login', [
                'email' => $this->config->getEmail(),
                'password' => $this->config->getPassword(),
            ]);

        if (! $response->successful()) {
            $this->throwFailedRequest('Failed to login to GoLog', $response);
        }

        $data = $response->json();

        Cache::put(self::CACHE_KEY, $data, 3000);

        return GoLogAuthResponseDTO::fromArray($data);
    }

    /**
     * @return array<int, GoLogTransportTypeResponseDTO>
     */
    public function getTransportTypes(): array
    {
        $response = $this->authenticatedRequest()->get('type-transport');

        if (! $response->successful()) {
            $this->throwFailedRequest('Failed to fetch transport types from GoLog', $response);
        }

        /** @var array<int, array<string, mixed>> $items */
        $items = $response->json();

        return array_map(
            static fn(array $item): GoLogTransportTypeResponseDTO => GoLogTransportTypeResponseDTO::fromArray($item),
            $items
        );
    }

    public function createTransportType(string $name, string $description, string $care): GoLogTransportTypeResponseDTO
    {
        $payload = [
            'name' => $name,
            'description' => $description,
            'care' => $care,
        ];

        $response = $this->post('type-transport', $payload, 'Failed to create transport type in GoLog');

        return GoLogTransportTypeResponseDTO::fromArray($response->json());
    }

    public function createUser(
        string $name,
        string $email,
        string $password,
        string $cpf,
        GoLogUserProfile $profile = GoLogUserProfile::DRIVER,
    ): GoLogUserResponseDTO {
        $payload = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'cpf' => $cpf,
            'User Profile' => $profile->value,
            'companyId' => $this->getCompanyId(),
        ];

        $response = $this->post('user', $payload, 'Failed to create user in GoLog');

        return GoLogUserResponseDTO::fromArray($response->json());
    }

    public function createDriver(
        string $userId,
        string $cnhNumber,
        string $cnhExpiration,
        float $costPerHour,
    ): GoLogDriverResponseDTO {
        $payload = [
            'userId' => $userId,
            'cnhNumber' => $cnhNumber,
            'cnhExpiration' => $cnhExpiration,
            'costPerHour' => $costPerHour,
        ];

        $response = $this->post('driver', $payload, 'Failed to create driver in GoLog');

        return GoLogDriverResponseDTO::fromArray($response->json());
    }

    public function createTractor(
        string $plate,
        string $renavam,
        string $model,
        int $numberAxles,
        float $maximumCapacity,
        GoLogFuelType $typeFuel,
        float $costPerKilometer,
        GoLogVehicleStatus $status = GoLogVehicleStatus::ATIVO,
    ): GoLogTractorResponseDTO {
        $payload = [
            'plate' => $plate,
            'renavam' => $renavam,
            'model' => $model,
            'numberAxles' => $numberAxles,
            'maximumCapacity' => $maximumCapacity,
            'Type Fuel' => $typeFuel->value,
            'costPerKilometer' => $costPerKilometer,
            'status' => $status->value,
            'companyId' => $this->getCompanyId(),
        ];

        $response = $this->post('tractor', $payload, 'Failed to create tractor in GoLog');

        return GoLogTractorResponseDTO::fromArray($response->json());
    }

    public function createEquipamentGroup(
        string $equipament1Id,
        ?string $observation = null,
    ): GoLogEquipamentGroupResponseDTO {
        $payload = array_filter([
            'equipament1Id' => $equipament1Id,
            'observation' => $observation,
        ], static fn(mixed $v): bool => $v !== null);

        $response = $this->post('equipament-group', $payload, 'Failed to create equipament group in GoLog');

        return GoLogEquipamentGroupResponseDTO::fromArray($response->json());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $endpoint, array $payload, string $errorMessage): Response
    {
        $response = $this->authenticatedRequest()->post($endpoint, $payload);

        if (! $response->successful()) {
            $this->throwFailedRequest($errorMessage, $response);
        }

        return $response;
    }

    private function authenticatedRequest(): PendingRequest
    {
        return $this->http->baseUrl($this->config->getBaseUrl())
            ->timeout($this->config->getTimeout())
            ->acceptJson()
            ->withToken($this->getToken());
    }

    private function throwFailedRequest(string $message, Response $response): void
    {
        throw new CultivaException(
            $response->status(),
            "$message Response: " . $response->body()
        );
    }
}
