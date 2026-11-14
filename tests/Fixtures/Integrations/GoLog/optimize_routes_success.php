<?php

return <<<JSON
{
  "routesCount": 1,
  "plannedShipments": 1,
  "transportIds": [
    "t1111111-1111-1111-1111-111111111111"
  ],
  "skipped": [
    {
      "shipmentIds": [
        "22222222-2222-2222-2222-222222222222"
      ],
      "label": "22222222-2222-2222-2222-222222222222",
      "reasons": [
        "fora da janela de horário das escalas"
      ]
    }
  ],
  "totalDistanceMeters": 248600,
  "totalCost": 1230.5
}
JSON;
