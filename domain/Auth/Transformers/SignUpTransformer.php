<?php

namespace Cultiva\Auth\Transformers;

use Cultiva\Auth\DTO\AuthTokensDTO;
use Cultiva\Auth\DTO\SignUpResultDTO;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\User\User;

/**
 * @nicolas
 *
 * Aqui tem um exemplo de transformer legal, tem um outro comentário no transformer da integração do Geo API que explicar
 * melhor os transformer. Leia ele antes de tentar entender isso aqui.
 *
 * Se tu reparar, cada método privado aqui poderia ser um tranformer indivual em sua respectiva pasta de models no domínio.
 * Por exemplo, o método transform address poderia viver como uma classe na pasta de transformer do model address e a gente
 * só injetaria ele no construtor dessa classe.
 *
 * Te dou a missão de fazer essa refatoração.
 */
final class SignUpTransformer
{

    public function transform(SignUpResultDTO $dto): array
    {
        $result =  [
            'user'         => $this->transformUser($dto->user),
            'profile_type' => $dto->profileType->value,
            'tokens'       => $this->transformTokens($dto->tokens),
        ];

        if ($dto->producer !== null) {
            $result['producer'] = $this->transformProducer($dto->producer);
        }

        if ($dto->retailer !== null) {
            $result['retailer'] = $this->transformRetailer($dto->retailer);
        }

        return $result;
    }

    private function transformUser(User $user): array
    {
        return [
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }

    private function transformProducer(?Producer $producer): ?array
    {
        if ($producer === null) {
            return null;
        }

        return [
            'trade_name'      => $producer->trade_name,
            'legal_name'      => $producer->legal_name,
            'is_company'      => $producer->is_company,
            'document_number' => $producer->document_number,
            'phone'           => $producer->phone,
            'address'         => $this->transformAddress($producer->address),
        ];
    }

    private function transformRetailer(?Retailer $retailer): ?array
    {
        if ($retailer === null) {
            return null;
        }

        return [
            'trade_name'      => $retailer->trade_name,
            'legal_name'      => $retailer->legal_name,
            'document_number' => $retailer->document_number,
            'business_type'   => $retailer->business_type->value,
            'phone'           => $retailer->phone,
            'address'         => $this->transformAddress($retailer->address),
        ];
    }

    private function transformAddress(Address $address): ?array
    {
        return [
            'zip'             => $address->zip,
            'street'          => $address->street,
            'number'          => $address->number,
            'complement'      => $address->complement,
            'reference_point' => $address->reference_point,
            'neighborhood'    => $address->neighborhood,
            'city'            => $address->city,
            'state'           => $address->state,
        ];
    }

    private function transformTokens(AuthTokensDTO $tokens): array
    {
        return [
            'access_token'  => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken,
        ];
    }
}
