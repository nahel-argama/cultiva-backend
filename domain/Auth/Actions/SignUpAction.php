<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Actions\SearchCepAction;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Producer\Actions\CreateProducerAction;
use Cultiva\Models\Retailer\Actions\CreateRetailerAction;
use Cultiva\Models\User\Actions\CreateUserAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class SignUpAction
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly CreateProducerAction $createProducer,
        private readonly CreateRetailerAction $createRetailer,
        private readonly GenerateTokenPairAction $generateTokens,
        private readonly SearchCepAction $searchCep,
    ) {}

    public function execute(SignUpDTO $dto): ProfileResultDTO
    {
        if ($dto->producer === null && $dto->retailer === null) {
            throw new CultivaException(422, Lang::get('auth.sign_up.user_without_profile'));
        }

        /**
         * @nicolas
         *
         * Conceito de cache lock: a gente só salva uma chave com o Time To Live (TTL) com um tempo X e liberamos ela
         * após o uso. Porque eu faço isso aqui? Caso mais de uma request seja mandada com o mesmo email e o processamento
         * demore, a gente garante que só uma request vai ser processada e as outras vão receber um erro de "processando" e não vão criar
         * mais de um usuário com o mesmo email (no caso o banco tem constraints, mas a gente evita de chegar no banco).
         */
        $lock = Cache::lock("signup:{$dto->user->email}", 60);

        if (! $lock->get()) {
            throw new CultivaException(422, Lang::get('auth.sign_up.in_progress'));
        }

        try {
            [$producerGeo, $retailerGeo] = $this->resolveGeocoding($dto);

            return $this->registerUser($dto, $producerGeo, $retailerGeo);
        } finally {
            $lock->release();
        }
    }

    private function registerUser(
        SignUpDTO $dto,
        ?GeoAddressDTO $producerGeo,
        ?GeoAddressDTO $retailerGeo,
    ): ProfileResultDTO {
        return DB::transaction(function () use ($dto, $producerGeo, $retailerGeo) {
            $user = $this->createUser->execute($dto->user);

            $producer = null;
            if ($dto->producer !== null) {
                $producer = $this->createProducer->execute($user->id, $dto->producer, $producerGeo);
            }

            $retailer = null;
            if ($dto->retailer !== null) {
                $retailer = $this->createRetailer->execute($user->id, $dto->retailer, $retailerGeo);
            }

            $tokens = $this->generateTokens->execute($user);

            return new ProfileResultDTO(
                user: $user,
                profileType: $dto->profileType,
                producer: $producer,
                retailer: $retailer,
                tokens: $tokens,
            );
        });
    }

    /**
     * @return array<GeoAddressDTO|null>
     */
    private function resolveGeocoding(SignUpDTO $dto): array
    {
        $retailerCep = isset($dto->retailer) ? new Cep($dto->retailer->address->zip) : null;
        $producerCep = isset($dto->producer) ? new Cep($dto->producer->address->zip) : null;

        $producerGeo = $producerCep ? $this->searchCep->execute($producerCep) : null;
        $retailerGeo = $retailerCep ? $this->searchCep->execute($retailerCep) : null;

        return [$producerGeo, $retailerGeo];
    }
}
