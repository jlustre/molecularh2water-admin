<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Enums\Crm\LeadLifecycle;
use App\Enums\Crm\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Crm\LeadSource;
use App\Models\Crm\Tag;
use App\Services\Crm\LeadService;
use App\Support\Crm\CrmContactResolver;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadImportExportController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->hasPermission('leads.export'), 403);

        $lifecycle = LeadLifecycle::from($request->string('lifecycle', 'lead')->toString());

        $filename = sprintf('%s-export-%s.csv', $lifecycle->value, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($lifecycle, $request) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'first_name',
                'last_name',
                'email',
                'phone',
                'city',
                'state',
                'country',
                'lifecycle',
                'status',
                'temperature',
                'score',
                'source',
                'interested_in',
                'message',
                'tags',
                'next_follow_up_at',
            ]);

            CrmScope::contacts(CrmContactResolver::queryFor($lifecycle))
                ->with(['source', 'tags'])
                ->orderBy('id')
                ->chunk(100, function ($contacts) use ($handle) {
                    foreach ($contacts as $contact) {
                        fputcsv($handle, [
                            $contact->first_name,
                            $contact->last_name,
                            $contact->email,
                            $contact->phone,
                            $contact->city,
                            $contact->state,
                            $contact->country,
                            $contact->lifecycleSlug()->value,
                            $contact->status?->value ?? $contact->status,
                            $contact->temperature->value,
                            $contact->score,
                            $contact->source?->slug,
                            $contact->interested_in,
                            $contact->message,
                            $contact->tags->pluck('name')->implode('|'),
                            $contact->next_follow_up_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('leads.import'), 403);

        $validated = $request->validate([
            'lifecycle' => ['required', Rule::in(['lead', 'prospect', 'client', 'recruit'])],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $lifecycle = LeadLifecycle::from($validated['lifecycle']);
        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $maxRows = (int) config('crm.import.max_rows', 500);
        $imported = 0;
        $user = $request->user();

        while (($row = fgetcsv($handle)) !== false && $imported < $maxRows) {
            if (count($row) === 1 && blank($row[0])) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));

            if (! $data || blank($data['first_name'] ?? null)) {
                continue;
            }

            $sourceSlug = $data['source'] ?? null;
            $leadSourceId = null;

            if ($sourceSlug) {
                $leadSourceId = LeadSource::query()
                    ->where('slug', Str::slug($sourceSlug))
                    ->value('id');
            }

            $tagIds = [];

            if (! empty($data['tags'])) {
                foreach (explode('|', $data['tags']) as $tagName) {
                    $tagName = trim($tagName);

                    if ($tagName === '') {
                        continue;
                    }

                    $tag = Tag::query()->firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName],
                    );
                    $tagIds[] = $tag->id;
                }
            }

            $status = LeadStatus::normalize($data['status'] ?? null) ?? LeadStatus::New;

            $this->leadService->create([
                'lifecycle' => $data['lifecycle'] ?? $lifecycle->value,
                'status' => $status->value,
                'temperature' => $data['temperature'] ?? 'cold',
                'score' => $data['score'] ?? 0,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? null,
                'lead_source_id' => $leadSourceId,
                'interested_in' => $data['interested_in'] ?? null,
                'message' => $data['message'] ?? null,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                'consent_given' => true,
            ], $user, $tagIds);

            $imported++;
        }

        fclose($handle);

        $indexRoute = match ($lifecycle) {
            LeadLifecycle::Prospect => CrmRoutes::name('prospects.index'),
            LeadLifecycle::Client => CrmRoutes::name('customers.index'),
            default => CrmRoutes::name('leads.index'),
        };

        return redirect()
            ->route($indexRoute)
            ->with('status', "{$imported} {$lifecycle->label()} record(s) imported successfully.");
    }
}
