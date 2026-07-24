<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotifiableForm;
use App\Http\Controllers\Controller;
use App\Models\EmailMapping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailMappingController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmailMapping::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('form_key') && array_key_exists($request->form_key, NotifiableForm::options())) {
            $query->where('form_key', $request->form_key);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        return view('admin.email-mappings.index', [
            'formOptions' => NotifiableForm::options(),
            'mappings' => $query
                ->paginate((int) $request->integer('per_page', 15))
                ->withQueryString(),
            'activeCount' => EmailMapping::query()->where('is_active', true)->count(),
            'inactiveCount' => EmailMapping::query()->where('is_active', false)->count(),
            'totalCount' => EmailMapping::query()->count(),
            'formCounts' => EmailMapping::query()
                ->selectRaw('form_key, count(*) as aggregate')
                ->groupBy('form_key')
                ->pluck('aggregate', 'form_key'),
        ]);
    }

    public function create(): View
    {
        return view('admin.email-mappings.create', [
            'formOptions' => NotifiableForm::options(),
            'mapping' => new EmailMapping([
                'is_active' => true,
                'form_key' => NotifiableForm::ContactUs,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shared = $this->validatedSharedAttributes($request);
        $emails = $this->validatedEmails($request);

        foreach ($emails as $email) {
            EmailMapping::query()->create([
                ...$shared,
                'email' => $email,
            ]);
        }

        $count = count($emails);

        return redirect()
            ->route('admin.email-mappings.index')
            ->with('status', $count === 1
                ? 'Email mapping created.'
                : "{$count} email mappings created.");
    }

    public function edit(EmailMapping $emailMapping): View
    {
        $formKey = $emailMapping->form_key instanceof NotifiableForm
            ? $emailMapping->form_key->value
            : (string) $emailMapping->form_key;

        $recipientEmails = EmailMapping::query()
            ->forForm($formKey)
            ->orderBy('email')
            ->pluck('email')
            ->map(fn ($email) => (string) $email)
            ->values()
            ->all();

        if ($recipientEmails === []) {
            $recipientEmails = [(string) $emailMapping->email];
        }

        return view('admin.email-mappings.edit', [
            'formOptions' => NotifiableForm::options(),
            'mapping' => $emailMapping,
            'recipientEmails' => $recipientEmails,
        ]);
    }

    public function update(Request $request, EmailMapping $emailMapping): RedirectResponse
    {
        $originalFormKey = $emailMapping->form_key instanceof NotifiableForm
            ? $emailMapping->form_key->value
            : (string) $emailMapping->form_key;

        $managedIds = EmailMapping::query()
            ->forForm($originalFormKey)
            ->pluck('id')
            ->all();

        $shared = $this->validatedSharedAttributes($request);
        $emails = $this->validatedEmails($request, $managedIds);

        $count = DB::transaction(function () use ($shared, $emails, $managedIds, $originalFormKey) {
            $keepIds = [];

            foreach ($emails as $email) {
                $mapping = EmailMapping::query()->updateOrCreate(
                    [
                        'form_key' => $shared['form_key'],
                        'email' => $email,
                    ],
                    [
                        ...$shared,
                        'email' => $email,
                    ],
                );

                $keepIds[] = $mapping->id;
            }

            EmailMapping::query()
                ->whereIn('id', $managedIds)
                ->whereNotIn('id', $keepIds)
                ->delete();

            if ($shared['form_key'] !== $originalFormKey) {
                EmailMapping::query()
                    ->forForm($shared['form_key'])
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }

            return count($emails);
        });

        return redirect()
            ->route('admin.email-mappings.index')
            ->with('status', $count === 1
                ? 'Email mapping updated.'
                : "{$count} email mappings updated.");
    }

    public function destroy(EmailMapping $emailMapping): RedirectResponse
    {
        $emailMapping->delete();

        return redirect()
            ->route('admin.email-mappings.index')
            ->with('status', 'Email mapping deleted.');
    }

    public function updateSeeder(): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $mappings = EmailMapping::query()
            ->orderBy('id')
            ->get([
                'id',
                'form_key',
                'email',
                'name',
                'is_active',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->map(fn (EmailMapping $mapping) => [
                'id' => $mapping->id,
                'form_key' => $mapping->form_key instanceof NotifiableForm
                    ? $mapping->form_key->value
                    : (string) $mapping->form_key,
                'email' => $mapping->email,
                'name' => $mapping->name,
                'is_active' => (bool) $mapping->is_active,
                'notes' => $mapping->notes,
                'created_at' => $mapping->created_at?->toDateTimeString(),
                'updated_at' => $mapping->updated_at?->toDateTimeString(),
            ])
            ->all();

        $exportedMappings = var_export($mappings, true);
        $generatedAt = now()->toDateTimeString();
        $path = database_path('seeders/EmailMappingsSeeder.php');

        File::put($path, <<<PHP
<?php

namespace Database\\Seeders;

use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Facades\\DB;

class EmailMappingsSeeder extends Seeder
{
    /**
     * Seed email mappings from the admin export generated at {$generatedAt}.
     */
    public function run(): void
    {
        \$mappings = {$exportedMappings};

        foreach (\$mappings as \$mapping) {
            DB::table('email_mappings')->updateOrInsert(
                ['id' => \$mapping['id']],
                \$mapping
            );
        }
    }
}
PHP);

        return redirect()
            ->route('admin.email-mappings.index')
            ->with('status', 'EmailMappingsSeeder.php updated with '.count($mappings).' mapping'.(count($mappings) === 1 ? '' : 's').'.');
    }

    /**
     * @return array{form_key: string, name: ?string, is_active: bool, notes: ?string}
     */
    private function validatedSharedAttributes(Request $request): array
    {
        $validated = $request->validate([
            'form_key' => ['required', 'string', Rule::in(array_keys(NotifiableForm::options()))],
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return [
            'form_key' => $validated['form_key'],
            'name' => $validated['name'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @param  list<int|string>  $ignoredIds
     * @return list<string>
     */
    private function validatedEmails(Request $request, array $ignoredIds = []): array
    {
        $request->merge([
            'emails' => collect($request->input('emails', []))
                ->when(
                    filled($request->input('email')),
                    fn ($emails) => $emails->push($request->input('email')),
                )
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'emails' => ['required', 'array', 'min:1', 'max:25'],
            'emails.*' => [
                'required',
                'email',
                'max:255',
                Rule::unique('email_mappings', 'email')->where(function ($query) use ($request, $ignoredIds) {
                    $query->where('form_key', $request->input('form_key'));

                    if ($ignoredIds !== []) {
                        $query->whereNotIn('id', $ignoredIds);
                    }
                }),
            ],
        ]);

        return array_values($validated['emails']);
    }
}
