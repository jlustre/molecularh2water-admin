<?php

namespace App\Enums;

enum NotifiableForm: string
{
    case ContactUs = 'contact_us';
    case WaterAwarenessShow = 'water_awareness_show';
    case HydrationSpecialistZoom = 'hydration_specialist_zoom';
    case WellnessAdvocateZoom = 'wellness_advocate_zoom';
    case WarrantyRegistration = 'warranty_registration';
    case InstallationQuestionnaire = 'installation_questionnaire';
    case IssueReport = 'issue_report';

    public function label(): string
    {
        return match ($this) {
            self::ContactUs => 'Contact Us',
            self::WaterAwarenessShow => 'Water Awareness Shows',
            self::HydrationSpecialistZoom => 'Hydration Specialist Zooms',
            self::WellnessAdvocateZoom => 'Wellness Advocate Zooms',
            self::WarrantyRegistration => 'Warranty Registrations',
            self::InstallationQuestionnaire => 'Installation Questionnaires',
            self::IssueReport => 'Issue Reports',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ContactUs => 'About page contact form submissions.',
            self::WaterAwarenessShow => 'Water Awareness Show schedule requests.',
            self::HydrationSpecialistZoom => 'Hydration Specialist Zoom requests.',
            self::WellnessAdvocateZoom => 'Wellness Advocate Zoom requests.',
            self::WarrantyRegistration => 'Public warranty registration submissions.',
            self::InstallationQuestionnaire => 'Pre-installation questionnaire submissions.',
            self::IssueReport => 'Website and admin issue / error reports from any user.',
        };
    }

    public function adminIndexRoute(): ?string
    {
        return match ($this) {
            self::ContactUs,
            self::WaterAwarenessShow,
            self::HydrationSpecialistZoom,
            self::WellnessAdvocateZoom => 'admin.website-forms.index',
            self::WarrantyRegistration => 'admin.warranty-registrations.index',
            self::InstallationQuestionnaire => 'admin.installation-questionnaires.index',
            self::IssueReport => 'admin.issue-reports.index',
        };
    }

    /**
     * @return array<int, string>|null
     */
    public function adminIndexRouteParams(): ?array
    {
        return match ($this) {
            self::ContactUs => ['formType' => WebsiteFormType::ContactUs->routeKey()],
            self::WaterAwarenessShow => ['formType' => WebsiteFormType::WaterAwarenessShow->routeKey()],
            self::HydrationSpecialistZoom => ['formType' => WebsiteFormType::HydrationSpecialistZoom->routeKey()],
            self::WellnessAdvocateZoom => ['formType' => WebsiteFormType::WellnessAdvocateZoom->routeKey()],
            default => null,
        };
    }

    public static function fromWebsiteFormType(WebsiteFormType $type): self
    {
        return match ($type) {
            WebsiteFormType::ContactUs => self::ContactUs,
            WebsiteFormType::WaterAwarenessShow => self::WaterAwarenessShow,
            WebsiteFormType::HydrationSpecialistZoom => self::HydrationSpecialistZoom,
            WebsiteFormType::WellnessAdvocateZoom => self::WellnessAdvocateZoom,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
