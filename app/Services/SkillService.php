<?php

namespace App\Services;

use App\Models\Skill;
use App\Repositories\Contracts\SkillRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SkillService
{
    public function __construct(private readonly SkillRepositoryInterface $skills)
    {
    }

    public function create(array $data, ?UploadedFile $image = null): Skill
    {
        if ($image) {
            $data['image'] = $this->storeImage($image);
        }

        return $this->skills->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'category_id' => $data['category_id'] ?? null,
            'image' => $data['image'] ?? null,
        ]);
    }

    public function update(Skill $skill, array $data, ?UploadedFile $image = null): Skill
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($image) {
            $this->deleteStoredImage($skill->image);
            $data['image'] = $this->storeImage($image);
        }

        return $this->skills->update(
            $skill,
            collect($data)->only(['name', 'slug', 'category_id', 'image'])->all(),
        );
    }

    public function delete(Skill $skill): void
    {
        $this->deleteStoredImage($skill->image);
        $this->skills->delete($skill);
    }

    private function storeImage(UploadedFile $file): string
    {
        $path = $file->store('skills', 'public');

        return $this->publicStorageUrl($path);
    }

    private function deleteStoredImage(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', $path), '/');
        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }

    private function publicStorageUrl(string $path): string
    {
        return rtrim(config('app.url'), '/').'/storage/'.$path;
    }
}
