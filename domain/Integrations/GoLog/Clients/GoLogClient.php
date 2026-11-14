<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Clients;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Config;
use Cultiva\Integrations\GoLog\DTO\GoLogAuthResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogDriverResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogEquipamentGroupResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportItemDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogOptimizeRoutesResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTractorResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogUserResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogWorkScheduleResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleStatus;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleType;
use Cultiva\Integrations\GoLog\Enums\GoLogWorkScheduleStatus;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoLogClient
{
    private const string CACHE_KEY = 'golog_auth_session';

    private const int TOKEN_CACHE_TTL_SECONDS = 6600;

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
        $tenantId = $this->config->getTenantId();

        if ($tenantId !== null) {
            return $tenantId;
        }

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

        Cache::put(self::CACHE_KEY, $data, self::TOKEN_CACHE_TTL_SECONDS);

        return GoLogAuthResponseDTO::fromArray($data);
    }

    /**
     * @return array<int, GoLogTransportTypeResponseDTO>
     */
    public function getTransportTypes(): array
    {
        $response = $this->get('type-transport', [], 'Failed to fetch transport types from GoLog');

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
            'userProfile' => $profile->value,
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
            'cnhExpiration' => Carbon::createFromFormat('Y-m-d', $cnhExpiration)->format('d/m/Y'),
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
        ?GoLogVehicleType $vehicleType = null,
        ?float $cargoVolume = null,
        ?float $kmPerLiter = null,
    ): GoLogTractorResponseDTO {
        $payload = array_filter([
            'plate' => $plate,
            'renavam' => $renavam,
            'model' => $model,
            'numberAxles' => $numberAxles,
            'maximumCapacity' => $maximumCapacity,
            'typeFuel' => $typeFuel->value,
            'costPerKilometer' => $costPerKilometer,
            'status' => $status->value,
            'companyId' => $this->getCompanyId(),
            'vehicleType' => $vehicleType?->value,
            'cargoVolume' => $cargoVolume,
            'kmPerLiter' => $kmPerLiter,
        ], static fn(mixed $value): bool => $value !== null);

        $response = $this->post('tractor', $payload, 'Failed to create tractor in GoLog');

        return GoLogTractorResponseDTO::fromArray($response->json());
    }

    /**
     * @return array<int, GoLogEquipamentGroupResponseDTO>
     */
    public function listEquipamentGroups(): array
    {
        $response = $this->get('equipament-group', [], 'Failed to fetch equipament groups from GoLog');
        $data = $response->json();

        /** @var array<int, array<string, mixed>> $items */
        $items = $data['content'] ?? $data;

        return array_map(
            static fn(array $item): GoLogEquipamentGroupResponseDTO => GoLogEquipamentGroupResponseDTO::fromArray($item),
            $items
        );
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

    public function createWorkSchedule(
        string $driverId,
        string $equipamentGroupId,
        string $scheduleDate,
        int $startHour = 8,
        int $startMinute = 0,
        int $endHour = 18,
        int $endMinute = 0,
        GoLogWorkScheduleStatus $status = GoLogWorkScheduleStatus::ATIVO,
    ): GoLogWorkScheduleResponseDTO {
        $payload = [
            'driverId' => $driverId,
            'equipamentGroupId' => $equipamentGroupId,
            'scheduleDate' => $scheduleDate,
            'startWorkday' => sprintf('%02d:%02d:00', $startHour, $startMinute),
            'endWorkday' => sprintf('%02d:%02d:00', $endHour, $endMinute),
            'status' => $status->value,
        ];

        $response = $this->post('workSchedule', $payload, 'Failed to create work schedule in GoLog');
        $data = $response->json();

        if (is_array($data) && isset($data['id'])) {
            return GoLogWorkScheduleResponseDTO::fromArray($data);
        }

        return $this->findWorkSchedule($driverId, $scheduleDate);
    }

    /**
     * @return array<int, GoLogWorkScheduleResponseDTO>
     */
    public function listActiveWorkSchedules(): array
    {
        $response = $this->get('workSchedule/getByStatus', ['status' => GoLogWorkScheduleStatus::ATIVO->value], 'Failed to fetch active work schedules from GoLog');

        $data = $response->json();

        /** @var array<int, array<string, mixed>> $schedules */
        $schedules = $data['content'] ?? $data;

        return array_map(
            static fn(array $schedule): GoLogWorkScheduleResponseDTO => GoLogWorkScheduleResponseDTO::fromArray($schedule),
            $schedules
        );
    }

    private function findWorkSchedule(string $driverId, string $scheduleDate): GoLogWorkScheduleResponseDTO
    {
        $response = $this->get('workSchedule', [], 'Failed to fetch work schedules from GoLog');

        /** @var array<int, array<string, mixed>> $schedules */
        $schedules = $response->json();

        foreach (array_reverse($schedules) as $schedule) {
            if (($schedule['driver']['id'] ?? null) === $driverId && ($schedule['scheduleDate'] ?? null) === $scheduleDate) {
                return GoLogWorkScheduleResponseDTO::fromArray($schedule);
            }
        }

        throw new CultivaException(502, 'Created work schedule was not returned by GoLog.');
    }

    /**
     * @param array<int, GoLogInvoiceImportItemDTO> $invoices
     */
    public function importInvoicesJson(array $invoices): GoLogInvoiceImportResponseDTO
    {
        $payload = array_map(
            static fn(GoLogInvoiceImportItemDTO $item): array => $item->toArray(),
            $invoices
        );

        $response = $this->post('integration/invoices/json', [
            'invoices' => $payload,
        ], 'Failed to import invoices to GoLog');

        return GoLogInvoiceImportResponseDTO::fromArray($response->json());
    }

    /**
     * @param array<int, string> $shipmentIds
     * @param array<int, string> $workScheduleIds
     */
    public function optimizeRoutes(
        array $shipmentIds,
        array $workScheduleIds,
        GoLogRoutePriority $routePriority = GoLogRoutePriority::TEMPO,
    ): GoLogOptimizeRoutesResponseDTO {
        $response = $this->post('api-route-optimization', [
            'shipmentIds' => $shipmentIds,
            'workScheduleIds' => $workScheduleIds,
            'routePriority' => $routePriority->value,
        ], 'Failed to optimize routes in GoLog');

        return GoLogOptimizeRoutesResponseDTO::fromArray($response->json());
    }

    /**
     * @param array<string, mixed> $query
     */
    private function get(string $endpoint, array $query, string $errorMessage): Response
    {
        return $this->sendAuthenticated('get', $endpoint, $query, $errorMessage);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $endpoint, array $payload, string $errorMessage): Response
    {
        return $this->sendAuthenticated('post', $endpoint, $payload, $errorMessage);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function patch(string $endpoint, array $payload, string $errorMessage): Response
    {
        return $this->sendAuthenticated('patch', $endpoint, $payload, $errorMessage);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function sendAuthenticated(string $method, string $endpoint, array $data, string $errorMessage): Response
    {
        /** @var Response $response */
        $response = $this->authenticatedRequest()->{$method}($endpoint, $data);

        if ($response->status() === 401) {
            Cache::forget(self::CACHE_KEY);
            $this->login();
            /** @var Response $response */
            $response = $this->authenticatedRequest()->{$method}($endpoint, $data);
        }

        Log::info('GoLog API response', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        if (! $response->successful()) {
            $this->throwFailedRequest($errorMessage, $response);
        }

        return $response;
    }

    private function authenticatedRequest(): PendingRequest
    {
        $request = $this->http->baseUrl($this->config->getBaseUrl())
            ->timeout($this->config->getTimeout())
            ->acceptJson()
            ->withToken($this->getToken());

        $tenantId = $this->config->getTenantId();

        if ($tenantId !== null) {
            $request = $request->withHeaders([
                'X-Tenant-Id' => $tenantId,
            ]);
        }

        return $request;
    }

    private function throwFailedRequest(string $message, Response $response): never
    {
        $data = $response->json();
        $detail = is_array($data) && is_string($data['message'] ?? null)
            ? ": {$data['message']}"
            : ' Response: ' . $response->body();

        throw new CultivaException(
            $response->status(),
            $message . $detail
        );
    }
}
