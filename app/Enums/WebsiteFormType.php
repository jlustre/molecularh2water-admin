<?php

namespace App\Enums;

enum WebsiteFormType: string
{
    case ContactUs = 'contact_us';
    case WaterAwarenessShow = 'water_awareness_show';
    case HydrationSpecialistZoom = 'hydration_specialist_zoom';
    case WellnessAdvocateZoom = 'wellness_advocate_zoom';

    public function label(): string
    {
        return match ($this) {
            self::ContactUs => 'Contact Us',
            self::WaterAwarenessShow => 'Water Awareness Shows',
            self::HydrationSpecialistZoom => 'Hydration Specialist Zooms',
            self::WellnessAdvocateZoom => 'Wellness Advocate Zooms',
        };
    }

    public function singularLabel(): string
    {
        return match ($this) {
            self::ContactUs => 'Contact message',
            self::WaterAwarenessShow => 'Water Awareness Show request',
            self::HydrationSpecialistZoom => 'Hydration Specialist Zoom request',
            self::WellnessAdvocateZoom => 'Wellness Advocate Zoom request',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ContactUs => 'Messages submitted from the About page contact form.',
            self::WaterAwarenessShow => 'Requests to attend or schedule a live Water Awareness Show.',
            self::HydrationSpecialistZoom => 'Requests to schedule a Hydration Specialist Zoom call.',
            self::WellnessAdvocateZoom => 'Requests to schedule a 15-minute Wellness Advocate Zoom.',
        };
    }

    public function formContext(): string
    {
        return match ($this) {
            self::ContactUs => 'about-contact',
            self::WaterAwarenessShow => 'water-awareness-show',
            self::HydrationSpecialistZoom => 'hydration-specialist-zoom',
            self::WellnessAdvocateZoom => 'wellness-advocate-zoom',
        };
    }

    public function frontendPath(): string
    {
        return match ($this) {
            self::ContactUs => '/about#contact',
            self::WaterAwarenessShow => '/about#schedule-water-awareness-show',
            self::HydrationSpecialistZoom => '/about#hydration-specialist',
            self::WellnessAdvocateZoom => '/about#wellness-advocate',
        };
    }

    public function routeKey(): string
    {
        return match ($this) {
            self::ContactUs => 'contact-us',
            self::WaterAwarenessShow => 'water-awareness-shows',
            self::HydrationSpecialistZoom => 'hydration-specialist-zooms',
            self::WellnessAdvocateZoom => 'wellness-advocate-zooms',
        };
    }

    public function navKey(): string
    {
        return 'website-forms-'.$this->routeKey();
    }

    public static function tryFromFormContext(?string $formContext): ?self
    {
        if ($formContext === null || $formContext === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->formContext() === $formContext) {
                return $case;
            }
        }

        return null;
    }

    public static function tryFromRouteKey(string $routeKey): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->routeKey() === $routeKey) {
                return $case;
            }
        }

        return null;
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
