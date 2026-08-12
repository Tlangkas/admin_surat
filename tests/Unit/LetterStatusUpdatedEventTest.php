<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\LetterStatusUpdated;
use App\Models\LetterRequest;
use App\Models\LetterTemplate;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterStatusUpdatedEventTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvent(): LetterStatusUpdated
    {
        $user = User::factory()->create(['role' => 'gukar']);
        $template = LetterTemplate::create(['name' => 'T', 'content' => '<p>{{ nama }}</p>', 'is_active' => true]);
        $request = LetterRequest::create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'status' => 'approved_admin',
            'payload_data' => [],
        ]);

        return new LetterStatusUpdated($request);
    }

    public function test_broadcast_on_returns_private_channel_for_owner(): void
    {
        $event = $this->makeEvent();

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $channel = $channels[0];
        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame('private-user.' . $event->letterRequest->user_id, $channel->name);
    }

    public function test_broadcast_as_returns_expected_name(): void
    {
        $this->assertSame('letter.status.updated', $this->makeEvent()->broadcastAs());
    }

    public function test_broadcast_with_contains_uuid_and_status(): void
    {
        $event = $this->makeEvent();

        $payload = $event->broadcastWith();

        $this->assertSame($event->letterRequest->uuid, $payload['uuid']);
        $this->assertSame('approved_admin', $payload['status']);
    }

    public function test_channel_is_an_instance_of_broadcast_channel(): void
    {
        $channel = $this->makeEvent()->broadcastOn()[0];

        $this->assertInstanceOf(Channel::class, $channel);
    }
}