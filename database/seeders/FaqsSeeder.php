<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What is hydrogen water?',
                'answer' => 'Hydrogen water, also called hydrogen-rich or hydrogen-enriched water, is regular water that contains dissolved molecular hydrogen gas, or H2. Similar to how carbonated water contains dissolved carbon dioxide gas, hydrogen water contains dissolved hydrogen gas. It is still water, but with available molecular hydrogen dissolved into it.',
                'sort_order' => 1,
            ],
            [
                'question' => "Doesn't water already have hydrogen in it because water is H2O?",
                'answer' => 'Water does contain hydrogen atoms, but those atoms are chemically bonded to oxygen as part of the H2O molecule. Molecular hydrogen water is different because it contains dissolved H2 gas, where two hydrogen atoms are bonded to each other and are not tied up inside the water molecule. That unbound H2 is the form discussed in hydrogen water research and education.',
                'sort_order' => 2,
            ],
            [
                'question' => 'How can I get my daily dose of hydrogen?',
                'answer' => 'Hydrogen can be delivered in different ways, including inhalation, but one of the simplest and most comfortable approaches is drinking hydrogen-rich water. Many studies use hydrogen water because it is easy to administer and can fit into a daily hydration routine.',
                'sort_order' => 3,
            ],
            [
                'question' => 'When is the best time to drink hydrogen water?',
                'answer' => 'Suggested routines can vary based on personal goals and wellness needs. A common approach is to drink fresh hydrogen-rich water in the morning, around workouts, and about 30 minutes before or after meals. It is also wise to drink more when it is hot, when you perspire, or when you consume dehydrating drinks such as coffee or alcohol. For many people, a simple rule is this: when you feel fatigued, pause and rehydrate with fresh hydrogen water.',
                'sort_order' => 4,
            ],
            [
                'question' => 'If water is hydrogen-rich, does that mean it is acidic?',
                'answer' => 'Not necessarily. Acidic water is related to hydrogen ions, written as H+. Hydrogen-rich water refers to neutral molecular hydrogen gas, written as H2, dissolved in water. H2 gas is different from H+ ions, so hydrogen-rich water is not automatically acidic simply because it contains molecular hydrogen.',
                'sort_order' => 5,
            ],
            [
                'question' => 'Does adding hydrogen to water create hydrogen peroxide?',
                'answer' => 'No. Hydrogen peroxide is H2O2, which means it contains an extra oxygen atom, not extra hydrogen. Molecular hydrogen gas does not bond to water molecules or create a new molecule such as H4O. It simply dissolves into the water. Hydrogen water and hydrogen peroxide are completely different substances.',
                'sort_order' => 6,
            ],
            [
                'question' => 'Will dissolved hydrogen gas immediately escape from the water?',
                'answer' => 'Hydrogen does begin to leave the water after it is produced, much like carbonation slowly leaves sparkling water. It does not disappear instantly, but concentration can drop over time depending on surface area, movement, temperature, and storage. For best results, hydrogen-rich water is usually discussed as something to drink fresh before it goes flat.',
                'sort_order' => 7,
            ],
            [
                'question' => 'How much hydrogen water should I drink to get the benefits?',
                'answer' => 'The ideal amount is still being studied. Research commonly discusses daily hydrogen amounts in the range of about 0.5 to 1.6 mg of H2 or more. If water contains 1 mg/L, also called 1 ppm, then two liters would provide about 2 mg of H2. The practical takeaway is consistency: drinking fresh hydrogen-rich water regularly is generally emphasized more than treating it as a one-time drink.',
                'sort_order' => 8,
            ],
            [
                'question' => "Isn't hydrogen gas explosive?",
                'answer' => 'Hydrogen gas can be explosive at certain concentrations in air, but dissolved hydrogen in water is not explosive. The amount used in hydrogen-rich water is very different from storing concentrated hydrogen gas. In the context of drinking water, the dissolved H2 is used in very small amounts.',
                'sort_order' => 9,
            ],
            [
                'question' => 'Is a hydrogen water machine considered a medical device?',
                'answer' => 'In the United States, hydrogen water machines are generally presented as wellness or hydration technology, not as medical devices approved to diagnose, treat, cure, or prevent disease. Some wellness centers, sports recovery settings, and anti-aging clinics discuss molecular hydrogen as part of wellness protocols. This website keeps the conversation educational and does not present the machine as medical treatment.',
                'sort_order' => 10,
            ],
            [
                'question' => 'Is molecular hydrogen safe?',
                'answer' => '<p>Molecular hydrogen is widely recognized for its strong safety profile and is naturally produced by the body during normal digestion, especially after eating fiber-rich foods. Unlike many wellness compounds, hydrogen is the smallest and lightest molecule in existence and has been studied for decades in scientific and medical settings, including deep-sea diving applications where concentrations were significantly higher than those found in hydrogen-rich water.</p><p>Because hydrogen-rich water simply adds dissolved molecular hydrogen gas to drinking water, many people view it as a gentle and practical addition to a healthy hydration routine. As always, individuals with medical conditions, concerns, or who are under medical care should consult a qualified healthcare professional before making changes to their wellness routine.</p>',
                'sort_order' => 11,
            ],
            [
                'question' => "When were hydrogen's wellness benefits first discovered?",
                'answer' => '<p>Interest in hydrogen\'s wellness potential dates back more than 200 years, with discussions of its unique properties appearing as early as 1798. However, modern scientific attention accelerated after a landmark 2007 publication in Nature Medicine by Dr. Shigeo Ohta\'s research group, which sparked global interest in molecular hydrogen research. Since then, studies and educational discussions around hydrogen have expanded across universities, researchers, and wellness communities worldwide.</p><p>Today, many people are exploring hydrogen-rich water as part of a healthier daily hydration routine because it is simple, convenient, and easy to incorporate into everyday life. While research continues to evolve, the key question for most individuals is whether adding hydrogen-rich water aligns with their personal wellness goals and lifestyle habits.</p>',
                'sort_order' => 12,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'status' => 'published',
                    'sort_order' => $faq['sort_order'],
                ],
            );
        }
    }
}
