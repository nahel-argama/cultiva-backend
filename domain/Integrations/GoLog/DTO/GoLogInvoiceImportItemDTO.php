<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogInvoiceImportItemDTO
{
    public function __construct(
        public string $invoiceNumber,
        public string $series,
        public string $accessKey,
        public string $typeOperation,
        public float $weight,
        public float $volume,
        public float $value,
        public string $scheduledDate,
        public string $notes,
        public string $senderName,
        public string $senderDocument,
        public string $customerName,
        public string $customerDocument,
        public string $customerEmail,
        public string $customerPhone,
        public string $street,
        public string $number,
        public string $district,
        public string $city,
        public string $state,
        public string $cep,
        public string $latitude,
        public string $longitude,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceNumber: $data['invoiceNumber'] ?? '',
            series: $data['series'] ?? '1',
            accessKey: $data['accessKey'] ?? '',
            typeOperation: $data['typeOperation'] ?? 'ENTREGA',
            weight: $data['weight'] ?? 1.0,
            volume: $data['volume'] ?? 0.01,
            value: $data['value'] ?? 1.0,
            scheduledDate: $data['scheduledDate'] ?? '',
            notes: $data['notes'] ?? '',
            senderName: $data['senderName'] ?? '',
            senderDocument: $data['senderDocument'] ?? '',
            customerName: $data['customerName'] ?? '',
            customerDocument: $data['customerDocument'] ?? '',
            customerEmail: $data['customerEmail'] ?? '',
            customerPhone: $data['customerPhone'] ?? '',
            street: $data['street'] ?? '',
            number: $data['number'] ?? '',
            district: $data['district'] ?? '',
            city: $data['city'] ?? '',
            state: $data['state'] ?? '',
            cep: $data['cep'] ?? '',
            latitude: $data['latitude'] ?? '',
            longitude: $data['longitude'] ?? '',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'invoiceNumber' => $this->invoiceNumber,
            'series' => $this->series,
            'accessKey' => $this->accessKey,
            'typeOperation' => $this->typeOperation,
            'weight' => $this->weight,
            'volume' => $this->volume,
            'value' => $this->value,
            'scheduledDate' => $this->scheduledDate,
            'notes' => $this->notes,
            'senderName' => $this->senderName,
            'senderDocument' => $this->senderDocument,
            'customerName' => $this->customerName,
            'customerDocument' => $this->customerDocument,
            'customerEmail' => $this->customerEmail,
            'customerPhone' => $this->customerPhone,
            'street' => $this->street,
            'number' => $this->number,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'cep' => $this->cep,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
