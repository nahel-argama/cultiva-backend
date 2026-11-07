<?php

declare(strict_types=1);

namespace Database\Seeders;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\User\User;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the local database with real GoLog drivers, vehicles, equipment groups,
 * and work schedules provisioned under Cultiva's GoLog tenant
 * (GOLOG_TENANT_ID=5647b3d4-f42f-4905-a617-896afa8dccef).
 */
class GoLogDriverSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->drivers() as $item) {
            DB::transaction(function () use ($item): void {
                $user = User::query()->updateOrCreate(
                    ['email' => $item['user']['email']],
                    [
                        'name' => $item['user']['name'],
                        'password' => $item['user']['password'],
                        'profile_type' => ProfileType::GOLOG_DRIVER,
                        'is_active' => true,
                        'is_verified' => true,
                    ],
                );

                $company = Company::query()->updateOrCreate(
                    ['document_number' => $item['company']['document_number']],
                    [
                        'user_id' => $user->id,
                        'trade_name' => $item['company']['trade_name'],
                        'legal_name' => $item['company']['legal_name'],
                        'phone' => $item['company']['phone'],
                    ],
                );

                Address::query()->updateOrCreate(
                    ['company_id' => $company->id],
                    [
                        'zip' => $item['address']['zip'],
                        'street' => $item['address']['street'],
                        'number' => $item['address']['number'],
                        'complement' => $item['address']['complement'],
                        'reference_point' => $item['address']['reference_point'],
                        'neighborhood' => $item['address']['neighborhood'],
                        'city' => $item['address']['city'],
                        'state' => $item['address']['state'],
                        'coordinate' => $item['address']['coordinate'],
                    ],
                );

                $driver = GoLogDriver::query()->updateOrCreate(
                    ['company_id' => $company->id],
                    [
                        'cpf' => $item['driver']['cpf'],
                        'golog_user_id' => $item['driver']['golog_user_id'],
                        'golog_driver_id' => $item['driver']['golog_driver_id'],
                        'golog_work_schedule_id' => $item['driver']['golog_work_schedule_id'],
                    ],
                );

                GoLogVehicle::query()->updateOrCreate(
                    ['golog_driver_id' => $driver->id],
                    [
                        'golog_tractor_id' => $item['vehicle']['golog_tractor_id'],
                        'golog_equipament_group_id' => $item['vehicle']['golog_equipament_group_id'],
                    ],
                );
            });
        }
    }

    /**
     * @return array<int, array<string, array<string, string>>>
     */
    private function drivers(): array
    {
        return [
            // Driver 1: Volvo FH 540 (Plate CST4B19, CNH 74829163504, Schedule 2026-11-10 08:00-18:00)
            [
                'user' => [
                    'name' => 'Henrique Costa Motorista',
                    'email' => 'henrique.costa@cultiva.com',
                    'password' => 'Password123!',
                ],
                'company' => [
                    'document_number' => '34718295000158',
                    'trade_name' => 'Costa Express Sul',
                    'legal_name' => 'Costa Logistica Refrigerada LTDA',
                    'phone' => '47998112233',
                ],
                'address' => [
                    'zip' => '89010025',
                    'street' => 'Rua Doutor Luiz de Freitas Melro',
                    'number' => '150',
                    'complement' => 'Doca 1',
                    'reference_point' => 'Centro Blumenau',
                    'neighborhood' => 'Centro',
                    'city' => 'Blumenau',
                    'state' => 'SC',
                    'coordinate' => 'POINT(-49.06611 -26.91944)',
                ],
                'driver' => [
                    'cpf' => '52948173060',
                    'golog_user_id' => '5c3d2b39-c067-4c0c-900d-2675f6c819d0',
                    'golog_driver_id' => '7769e02b-87d9-475f-b400-6c4df77d14aa',
                    'golog_work_schedule_id' => 'a6d6156e-39f8-4156-a208-d32e0a578def',
                ],
                'vehicle' => [
                    'golog_tractor_id' => 'c4bef783-d583-4c1f-9142-4bf6ee121596',
                    'golog_equipament_group_id' => '5c0fd084-b358-4aae-aed0-2282963c26ca',
                ],
            ],
            // Driver 2: Scania R 450 (Plate RCH7D22, CNH 63928174500, Schedule 2026-11-10 07:30-17:30)
            [
                'user' => [
                    'name' => 'Camila Rocha Motorista',
                    'email' => 'camila.rocha@cultiva.com',
                    'password' => 'Password123!',
                ],
                'company' => [
                    'document_number' => '58291473000175',
                    'trade_name' => 'Rocha Translog SC',
                    'legal_name' => 'Camila Rocha Transportes Rodoviarios LTDA',
                    'phone' => '47991827364',
                ],
                'address' => [
                    'zip' => '89010025',
                    'street' => 'Rua Doutor Luiz de Freitas Melro',
                    'number' => '412',
                    'complement' => 'Box 04',
                    'reference_point' => 'Centro Logistico Blumenau',
                    'neighborhood' => 'Centro',
                    'city' => 'Blumenau',
                    'state' => 'SC',
                    'coordinate' => 'POINT(-49.06611 -26.91944)',
                ],
                'driver' => [
                    'cpf' => '34729185005',
                    'golog_user_id' => 'ae047dbf-71a1-42c9-8962-4d81458dd76d',
                    'golog_driver_id' => 'bc936c40-f0c2-45a7-bb69-721a10888cd4',
                    'golog_work_schedule_id' => '0b71d952-5754-48ca-982f-ebf5dc53d0ae',
                ],
                'vehicle' => [
                    'golog_tractor_id' => '52261194-1f73-4e3f-a4df-2bc6a9bf4f00',
                    'golog_equipament_group_id' => '820c342d-3430-457b-8d4d-2f1fcbed8ff1',
                ],
            ],
            // Driver 3: Mercedes-Benz Actros 2651 (Plate ALM3F88, CNH 52837194664, Schedule 2026-11-10 08:00-18:00)
            [
                'user' => [
                    'name' => 'Thiago Almeida Motorista',
                    'email' => 'thiago.almeida@cultiva.com',
                    'password' => 'Password123!',
                ],
                'company' => [
                    'document_number' => '46182937000126',
                    'trade_name' => 'Almeida Carga Viva',
                    'legal_name' => 'Almeida Logistica Agropecuaria LTDA',
                    'phone' => '47988374651',
                ],
                'address' => [
                    'zip' => '89010025',
                    'street' => 'Rua Doutor Luiz de Freitas Melro',
                    'number' => '1050',
                    'complement' => 'Galpao C',
                    'reference_point' => 'Proximo ao trevo norte',
                    'neighborhood' => 'Centro',
                    'city' => 'Blumenau',
                    'state' => 'SC',
                    'coordinate' => 'POINT(-49.06611 -26.91944)',
                ],
                'driver' => [
                    'cpf' => '28471936003',
                    'golog_user_id' => '0cd342e7-64bb-42a2-be08-82796ef61495',
                    'golog_driver_id' => '63a2dc1a-4362-453c-a753-20c42e30bb4a',
                    'golog_work_schedule_id' => '3197e9b3-686a-41f0-8905-c60768db49be',
                ],
                'vehicle' => [
                    'golog_tractor_id' => 'a4fb1bf2-29f7-4aa5-ac93-1a24a6dec510',
                    'golog_equipament_group_id' => 'c432a029-2c73-43fe-8ae8-b98fee04a8bb',
                ],
            ],
        ];
    }
}
