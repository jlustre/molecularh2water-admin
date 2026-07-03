<?php

namespace App\Livewire\Crm;

use App\Livewire\Crm\Concerns\UsesCrmLayout;
use App\Models\Crm\Funnel;
use App\Models\Crm\LandingPage;
use App\Services\Crm\LandingPageService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LandingPageManager extends Component
{
    use UsesCrmLayout;
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingPageId = null;

    public string $title = '';

    public string $slug = '';

    public string $headline = '';

    public string $subheadline = '';

    public string $hero_media = '';

    public string $cta_label = '';

    public string $cta_url = '';

    public string $thank_you_headline = '';

    public string $thank_you_body = '';

    public string $tracking_source = 'Landing Page';

    public ?int $funnel_id = null;

    public bool $is_published = false;

    public string $assignment = 'round_robin';

    public string $redirect_url = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('landing-pages.view'), 403);
    }

    public function openForm(?int $pageId = null): void
    {
        abort_unless(auth()->user()?->hasPermission('landing-pages.manage'), 403);

        if ($pageId) {
            $page = LandingPage::query()->with('form')->findOrFail($pageId);
            $this->editingPageId = $page->id;
            $this->title = $page->title;
            $this->slug = $page->slug;
            $this->headline = $page->headline ?? '';
            $this->subheadline = $page->subheadline ?? '';
            $this->hero_media = $page->hero_media ?? '';
            $this->cta_label = $page->cta_label ?? '';
            $this->cta_url = $page->cta_url ?? '';
            $this->thank_you_headline = $page->thank_you_headline ?? '';
            $this->thank_you_body = $page->thank_you_body ?? '';
            $this->tracking_source = $page->tracking_source ?? 'Landing Page';
            $this->funnel_id = $page->funnel_id;
            $this->is_published = $page->is_published;
            $this->assignment = $page->form?->settings['assignment'] ?? 'round_robin';
            $this->redirect_url = $page->form?->settings['redirect_url'] ?? '';
        } else {
            $this->resetForm();
        }

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(LandingPageService $landingPageService): void
    {
        abort_unless(auth()->user()?->hasPermission('landing-pages.manage'), 403);

        $data = $this->validate($this->rules());
        $payload = array_merge($data, [
            'form_settings' => [
                'assignment' => $data['assignment'],
                'lifecycle' => 'prospect',
                'redirect_url' => $data['redirect_url'] ?: null,
            ],
        ]);

        if ($this->editingPageId) {
            $page = LandingPage::query()->findOrFail($this->editingPageId);
            $landingPageService->update($page, $payload);
            session()->flash('status', 'Landing page updated.');
        } else {
            $landingPageService->create($payload);
            session()->flash('status', 'Landing page created.');
        }

        $this->closeForm();
    }

    public function togglePublish(int $pageId, LandingPageService $landingPageService): void
    {
        abort_unless(auth()->user()?->hasPermission('landing-pages.manage'), 403);

        $page = LandingPage::query()->findOrFail($pageId);
        $landingPageService->update($page, [
            'title' => $page->title,
            'is_published' => ! $page->is_published,
        ]);

        session()->flash('status', $page->fresh()->is_published ? 'Landing page published.' : 'Landing page unpublished.');
    }

    public function deletePage(int $pageId, LandingPageService $landingPageService): void
    {
        abort_unless(auth()->user()?->hasPermission('landing-pages.manage'), 403);

        $page = LandingPage::query()->findOrFail($pageId);

        try {
            $landingPageService->delete($page);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->addError('page', $exception->errors()['page'][0] ?? 'Unable to delete landing page.');

            return;
        }

        session()->flash('status', 'Landing page deleted.');
    }

    public function render()
    {
        $pages = Schema::hasTable('landing_pages')
            ? LandingPage::query()
                ->with(['funnel', 'form'])
                ->when($this->search, fn ($q) => $q->where(function ($inner) {
                    $inner->where('title', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                }))
                ->latest()
                ->paginate(12)
            : collect();

        return view('livewire.crm.landing-page-manager', [
            'pages' => $pages,
            'funnels' => Funnel::query()->where('is_active', true)->orderBy('name')->get(),
        ])->layout($this->crmLayout());
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:255'],
            'hero_media' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'url', 'max:500'],
            'thank_you_headline' => ['nullable', 'string', 'max:255'],
            'thank_you_body' => ['nullable', 'string', 'max:5000'],
            'tracking_source' => ['nullable', 'string', 'max:120'],
            'funnel_id' => ['nullable', 'exists:funnels,id'],
            'is_published' => ['boolean'],
            'assignment' => ['required', Rule::in(['none', 'round_robin'])],
            'redirect_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingPageId',
            'title',
            'slug',
            'headline',
            'subheadline',
            'hero_media',
            'cta_label',
            'cta_url',
            'thank_you_headline',
            'thank_you_body',
            'redirect_url',
        ]);
        $this->tracking_source = 'Landing Page';
        $this->is_published = false;
        $this->assignment = 'round_robin';
        $this->funnel_id = Funnel::query()->where('is_default', true)->value('id');
    }
}
