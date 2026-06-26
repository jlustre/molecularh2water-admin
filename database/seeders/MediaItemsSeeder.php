<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaItemsSeeder extends Seeder
{
    /**
     * Seed the media library from the admin export generated at 2026-06-26 11:42:11.
     */
    public function run(): void
    {
        $mediaItems = array (
  0 => 
  array (
    'id' => 1,
    'title' => '10 Studies On The Benefits of Molecular Hydrogen For Health',
    'category' => 'documents',
    'status' => 'published',
    'url' => NULL,
    'file_path' => 'media/documents/iLxCSnMcxj9ue1prYOhqD782FSxRpatH6CATtVEh.pdf',
    'description' => 'Explore a collection of scientific studies examining the potential role of molecular hydrogen in supporting wellness, oxidative stress awareness, and overall health research.',
    'file_name' => '10 Studies On The Benefits of Molecular Hydrogen For Health.pdf',
    'file_size' => 724740,
    'mime_type' => 'application/pdf',
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 02:36:36',
    'updated_at' => '2026-06-25 02:50:51',
  ),
  1 => 
  array (
    'id' => 2,
    'title' => 'The Machine User Manual',
    'category' => 'documents',
    'status' => 'published',
    'url' => NULL,
    'file_path' => 'media/documents/XORHscGs9V0RV3z55mCj7B1UanlNzEjznst3ESRg.pdf',
    'description' => 'A comprehensive guide covering setup, operation, maintenance, troubleshooting, and best practices to help you get the most from your hydrogen water machine.',
    'file_name' => 'The Machine User Manual.pdf',
    'file_size' => 4368496,
    'mime_type' => 'application/pdf',
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 13:14:20',
    'updated_at' => '2026-06-25 13:28:35',
  ),
  2 => 
  array (
    'id' => 3,
    'title' => 'Welcome to Happy Hydration Systems',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1202836206',
    'file_path' => NULL,
    'description' => 'Watch a brief introduction to our hydrogen water technology and discover how it fits into a healthier daily hydration routine. Learn the basics of molecular hydrogen, key machine features, and why so many families are exploring this innovative wellness solution.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 13:37:48',
    'updated_at' => '2026-06-25 13:41:54',
  ),
  3 => 
  array (
    'id' => 4,
    'title' => 'Hydrogen Facts That Will Blow Your Mind!',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1189725929',
    'file_path' => NULL,
    'description' => 'Discover fascinating facts about hydrogen—the most abundant element in the universe—and learn why it has become a growing topic of interest in science, technology, and wellness.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 13:57:53',
    'updated_at' => '2026-06-25 13:57:53',
  ),
  4 => 
  array (
    'id' => 5,
    'title' => 'The Iodine Test for H₂ Water',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1196558374',
    'file_path' => NULL,
    'description' => 'Learn how the iodine test is used to visually demonstrate the reducing properties of hydrogen-rich water through a simple and engaging experiment.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 14:00:39',
    'updated_at' => '2026-06-25 14:00:55',
  ),
  5 => 
  array (
    'id' => 6,
    'title' => 'Hydrogen Concentration Test',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1196558849',
    'file_path' => NULL,
    'description' => 'See how hydrogen concentration is measured and verified, helping demonstrate the level of dissolved molecular hydrogen present in hydrogen-rich water.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 14:02:37',
    'updated_at' => '2026-06-25 14:02:37',
  ),
  6 => 
  array (
    'id' => 7,
    'title' => 'Oxidation Reduction Test',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1197101298',
    'file_path' => NULL,
    'description' => 'Learn how oxidation-reduction (ORP) testing is used to evaluate the electron-donating properties of water and explore its role in hydrogen water demonstrations.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 14:05:06',
    'updated_at' => '2026-06-25 14:05:06',
  ),
  7 => 
  array (
    'id' => 8,
    'title' => 'Microcluster / Absorbancy Test',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1197101472',
    'file_path' => NULL,
    'description' => 'Explore a visual demonstration that compares water absorption and spreading characteristics, often used to illustrate the concept of micro-clustered water.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 14:06:38',
    'updated_at' => '2026-06-25 14:06:38',
  ),
  8 => 
  array (
    'id' => 9,
    'title' => 'Get Your Free Shower Filter Video',
    'category' => 'videos',
    'status' => 'published',
    'url' => 'https://vimeo.com/1200784501',
    'file_path' => NULL,
    'description' => 'Watch this quick video to learn how you may qualify for a free shower filter and discover the benefits of cleaner, more refreshing water for your daily routine.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-25 14:13:17',
    'updated_at' => '2026-06-25 14:13:29',
  ),
  9 => 
  array (
    'id' => 10,
    'title' => 'Hydrogen acts as a therapeutic antioxidant by selectively reducing cytotoxic oxygen radicals',
    'category' => 'links',
    'status' => 'published',
    'url' => 'https://pubmed.ncbi.nlm.nih.gov/17486089/',
    'file_path' => NULL,
    'description' => 'The groundbreaking paper that launched modern molecular hydrogen research.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 10:16:56',
    'updated_at' => '2026-06-26 11:39:15',
  ),
  10 => 
  array (
    'id' => 12,
    'title' => 'Recent Progress Toward Hydrogen Medicine',
    'category' => 'documents',
    'status' => 'published',
    'url' => NULL,
    'file_path' => 'media/documents/KPH3ZXDzatQmVhzzbHSXYLqiyUlOzpevfCEaQdbv.pdf',
    'description' => 'The groundbreaking paper that launched modern molecular hydrogen research.',
    'file_name' => 'CPD-17-2241.pdf',
    'file_size' => 883703,
    'mime_type' => 'application/pdf',
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 10:27:28',
    'updated_at' => '2026-06-26 10:27:28',
  ),
  11 => 
  array (
    'id' => 13,
    'title' => 'Recent progress toward hydrogen medicine',
    'category' => 'links',
    'status' => 'published',
    'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC3257754/',
    'file_path' => NULL,
    'description' => 'Recent progress toward hydrogen medicine: potential of molecular hydrogen for preventive and therapeutic applications',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 10:58:45',
    'updated_at' => '2026-06-26 10:59:40',
  ),
  12 => 
  array (
    'id' => 14,
    'title' => 'Hydrogen-Rich Water Reduces Inflammatory Responses and Prevents Apoptosis of Peripheral Blood Cells in Healthy Adults',
    'category' => 'links',
    'status' => 'published',
    'url' => 'https://www.nature.com/articles/s41598-020-68930-2',
    'file_path' => NULL,
    'description' => 'This randomized, double-blind, placebo-controlled clinical trial investigated the effects of drinking hydrogen-rich water for four weeks in healthy adults. The study found that hydrogen-rich water may enhance the body\'s antioxidant capacity, reduce inflammatory signaling, and help protect immune cells from oxidative damage, supporting its potential role in promoting overall cellular health.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 11:14:20',
    'updated_at' => '2026-06-26 11:28:10',
  ),
  13 => 
  array (
    'id' => 15,
    'title' => 'The Hydrogen Molecule as Antioxidant Therapy: Clinical Application in Hemodialysis and Perspectives',
    'category' => 'links',
    'status' => 'published',
    'url' => 'https://link.springer.com/article/10.1186/s41100-016-0036-0',
    'file_path' => NULL,
    'description' => 'This scientific review examines the therapeutic potential of molecular hydrogen in reducing oxidative stress and inflammation, with a focus on patients undergoing hemodialysis. It highlights how hydrogen-enriched water and dialysis solutions may help protect cells from oxidative damage, improve clinical outcomes, and pave the way for broader medical applications of hydrogen therapy.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 11:16:55',
    'updated_at' => '2026-06-26 11:26:57',
  ),
  14 => 
  array (
    'id' => 16,
    'title' => 'Molecular hydrogen: a therapeutic antioxidant and beyond',
    'category' => 'links',
    'status' => 'published',
    'url' => 'https://pmc.ncbi.nlm.nih.gov/articles/PMC5223313',
    'file_path' => NULL,
    'description' => 'An in-depth scientific review exploring how molecular hydrogen may help reduce oxidative stress, support healthy cellular function, and influence multiple biological pathways beyond its antioxidant properties. It summarizes emerging research while highlighting the need for continued clinical studies.',
    'file_name' => NULL,
    'file_size' => NULL,
    'mime_type' => NULL,
    'thumbnail_path' => NULL,
    'thumbnail_name' => NULL,
    'thumbnail_size' => NULL,
    'thumbnail_mime_type' => NULL,
    'created_at' => '2026-06-26 11:20:01',
    'updated_at' => '2026-06-26 11:20:01',
  ),
);

        $this->seedMediaFiles($mediaItems);

        foreach ($mediaItems as $mediaItem) {
            DB::table('media_items')->updateOrInsert(
                ['id' => $mediaItem['id']],
                $mediaItem
            );
        }
    }
    /**
     * @param array<int, array<string, mixed>> $mediaItems
     */
    private function seedMediaFiles(array $mediaItems): void
    {
        foreach ($mediaItems as $mediaItem) {
            $filePath = $mediaItem['file_path'] ?? null;

            if (! $filePath || Storage::disk('public')->exists($filePath)) {
                continue;
            }

            $seedFilePath = database_path('seeders/'.ltrim($filePath, '/'));

            if (! file_exists($seedFilePath)) {
                continue;
            }

            Storage::disk('public')->put($filePath, file_get_contents($seedFilePath));
        }
    }
}