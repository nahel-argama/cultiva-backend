<?php

namespace Cultiva\Auth\Requests;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @nicolas
 *
 * Aqui eu to usando um estilo de request BFF, que se baseia em facilitar a vida do frontend deixando o payload
 * basicamente igual ao form que o front vai montar.
 *
 * Isso é um request gordo blz? Não vai ser o que tu vai usar sempre, é mais por conta de ser um cadastro e pah. E também
 * isso aqui tá bem suscetível a mudança
 *
 * Eu também tento fazer a maior parte das validações aqui já, pra aliviar o fluxo das actios e coisa do tipo.
 *
 * A gente pode discutir melhor esse fluxo de cadastro, eu queria fazer no estilo stepper, em que o back salvaria onde o user
 * parou no login sabe? Mas fiz essa abordagem mais simples pra funcionar
 */
final class SignUpRequest extends FormRequest
{

    private const string PHONE_REGEX = '/^[0-9]{13}$/';
    private const string ZIP_REGEX = '/^[0-9]{8}$/';
    private const string DOCUMENT_REGEX = '/^[0-9]{11,14}$/';

    public function rules(): array
    {
        $rules = [
            'profile_type'  => ['required', Rule::enum(ProfileType::class)],
            'user.name'     => ['required', 'string', 'max:100'],
            'user.email'    => ['required', 'string', 'email', 'max:100', 'unique:users,email'],
            'user.password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        return match ($this->enum('profile_type', ProfileType::class)) {
            ProfileType::PRODUCER => [...$rules, ...$this->producerRules()],
            ProfileType::RETAILER => [...$rules, ...$this->retailerRules()],
        };
    }

    private function producerRules(): array
    {
        $producerDocRule = $this->boolean('producer.is_company') ? 'digits:14' : 'digits:11';

        return [
            'producer.trade_name'      => ['required', 'string', 'max:100'],
            'producer.legal_name'      => ['nullable', 'string', 'max:100'],
            'producer.is_company'      => ['required', 'boolean'],
            'producer.document_number' => [
                'required',
                'string',
                $producerDocRule,
                'regex:' . self::DOCUMENT_REGEX,
                'unique:producers,document_number',
            ],
            'producer.phone'           => ['required', 'string', 'max:15', 'regex:' . self::PHONE_REGEX],

            ...$this->addressRules('producer.address'),
        ];
    }

    private function retailerRules(): array
    {
        return [
            'retailer.trade_name'      => ['required', 'string', 'max:100'],
            'retailer.legal_name'      => ['nullable', 'string', 'max:100'],
            'retailer.document_number' => [
                'required',
                'string',
                'digits:14',
                'regex:' . self::DOCUMENT_REGEX,
                'unique:retailers,document_number',
            ],
            'retailer.business_type'   => ['required', Rule::enum(BusinessType::class)],
            'retailer.phone'           => ['required', 'string', 'max:15', 'regex:' . self::PHONE_REGEX],

            ...$this->addressRules('retailer.address'),
        ];
    }

    private function addressRules(string $prefix): array
    {
        return [
            "{$prefix}.zip"             => ['required', 'string', 'digits:8', 'regex:' . self::ZIP_REGEX],
            "{$prefix}.number"          => ['required', 'string', 'max:20'],
            "{$prefix}.complement"      => ['nullable', 'string', 'max:50'],
            "{$prefix}.reference_point" => ['nullable', 'string', 'max:150'],
        ];
    }
}
