<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ChatService;

class ChatServiceTest extends TestCase
{
    public function test_chat_service_instantiation()
    {
        $service = new ChatService();
        $this->assertInstanceOf(ChatService::class, $service);
    }

    public function test_profanity_filtering()
    {
        $service = new ChatService();
        $text = 'This is a clean string';
        $this->assertEquals($text, $service->filterProfanity($text));

        // If CensorWords is present, test it. If not, it should just return the original.
        if (class_exists('Snipe\BanBuilder\CensorWords')) {
             $this->assertNotEquals('fuck', $service->filterProfanity('fuck'));
        } else {
             $this->assertEquals('fuck', $service->filterProfanity('fuck'));
        }
    }
}
