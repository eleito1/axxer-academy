<?php

namespace Tests\Unit;

use App\Services\VideoEmbedUrl;
use PHPUnit\Framework\TestCase;

class VideoEmbedUrlTest extends TestCase
{
    public function test_it_converts_youtube_and_drive_urls_to_embed_urls(): void
    {
        $service = new VideoEmbedUrl;

        $this->assertSame('https://www.youtube-nocookie.com/embed/abcdefghijk?start=0&enablejsapi=1', $service->from('https://www.youtube.com/watch?v=abcdefghijk'));
        $this->assertSame('https://drive.google.com/file/d/file-id-123/preview', $service->from('https://drive.google.com/file/d/file-id-123/view?usp=sharing'));
        $this->assertSame('https://axxeracademy.com.br/videos/creator-1/curso-3/aula-18/aula.mp4', $service->from('https://axxeracademy.com.br/videos/creator-1/curso-3/aula-18/aula.mp4'));
    }
}
