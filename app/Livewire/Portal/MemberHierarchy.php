<?php

namespace App\Livewire\Portal;

use App\Services\SponsorHierarchyService;
use Livewire\Component;

class MemberHierarchy extends Component
{
    /** @var list<array{id: int, name: string, email: string, children: list<mixed>}> */
    public array $tree = [];

    /** @var list<array{id: int, name: string, email: string}> */
    public array $upline = [];

    public int $downlineCount = 0;

    public function mount(SponsorHierarchyService $sponsors): void
    {
        abort_unless(auth()->user()?->hasPermission('sponsors.view-tree'), 403);

        $user = auth()->user();

        $this->tree = [$sponsors->treeFor($user)];
        $this->upline = $sponsors->upline($user)
            ->reverse()
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ])
            ->values()
            ->all();
        $this->downlineCount = max(0, $sponsors->descendants($user)->count());
    }

    public function render()
    {
        return view('livewire.portal.member-hierarchy')
            ->layout('layouts.portal', ['header' => 'Member Hierarchy']);
    }
}
