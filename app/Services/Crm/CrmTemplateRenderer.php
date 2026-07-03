<?php

namespace App\Services\Crm;

use App\Models\Crm\Lead;

class CrmTemplateRenderer
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public function render(?string $template, Lead $lead, array $extra = []): string
    {
        if ($template === null || $template === '') {
            return '';
        }

        $variables = array_merge($this->leadVariables($lead), $extra);

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            fn (array $matches) => (string) ($variables[$matches[1]] ?? ''),
            $template,
        ) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    public function leadVariables(Lead $lead, array $extra = []): array
    {
        return array_merge([
            'first_name' => $lead->first_name ?? '',
            'last_name' => $lead->last_name ?? '',
            'lead_name' => $lead->fullName(),
            'email' => $lead->email ?? '',
            'phone' => $lead->phone ?? '',
            'company' => $lead->company ?? '',
        ], collect($extra)->map(fn ($value) => (string) $value)->all());
    }
}
