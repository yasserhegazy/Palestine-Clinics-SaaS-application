<?php

namespace App\Http\Resources\Admin\Dashboard;

use App\Http\Resources\Admin\Clinic\ClinicSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_clinics' => $this->resource['total_clinics'],
            'active_users' => $this->resource['active_users'],
            'total_patients' => $this->resource['total_patients'],
            'monthly_revenue' => $this->resource['monthly_revenue'],
            'recent_clinics' => ClinicSummaryResource::collection($this->resource['recent_clinics']),
            'revenue_history' => $this->resource['revenue_history'],
            'clinic_growth' => $this->resource['clinic_growth'],
        ];
    }
}
