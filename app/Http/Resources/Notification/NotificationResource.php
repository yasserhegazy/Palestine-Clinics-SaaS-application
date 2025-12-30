<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        return [
            'id' => $this->id,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'category' => $data['category'] ?? null,
            'cta' => $data['cta'] ?? null,
            'payload' => $data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
