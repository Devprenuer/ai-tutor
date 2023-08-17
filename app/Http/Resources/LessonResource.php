<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lesson = [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'difficulty_level' => $this->difficulty_level,
            'created_at' => $this->created_at,
            'view_count' => $this->view_count,
            'link' => route('lesson.show', $this->id) .'?' .http_build_query($request->query()),
        ];

        if (isset($this->body)) {
            $lesson['body'] = $this->body;
        }

        return $lesson;
    }
}
