<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollectionResource extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'payments' => PaymentResource::collection($this->collection),
        ];
    }
}
