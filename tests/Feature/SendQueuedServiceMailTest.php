<?php

namespace Tests\Feature;

use App\Jobs\SendQueuedServiceMail;
use App\Mail\FormTemplateMail;
use App\Models\ServiceRequest;
use App\Models\TourRequest;
use App\Models\TourRequestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendQueuedServiceMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_mail_restores_the_request_without_loaded_tour_relations(): void
    {
        $tour = TourRequest::factory()->has(TourRequestItem::factory(), 'items')->create();
        $request = $tour->serviceRequest->load(['client', 'tourRequest.items']);
        $job = new SendQueuedServiceMail(new FormTemplateMail('Tour confirmed', '<p>Accepted</p>'), 'client@example.test', $request, 'Email sent.', 'Email failed');

        $restored = unserialize(serialize($job));

        $this->assertInstanceOf(ServiceRequest::class, $restored->serviceRequest);
        $this->assertSame($request->id, $restored->serviceRequest->id);
        $this->assertSame([], $restored->serviceRequest->getRelations());
        $this->assertSame('Tour confirmed', $restored->mailable->renderedSubject);
        $this->assertSame('client@example.test', $restored->recipientEmail);
        $this->assertTrue($request->relationLoaded('tourRequest'));
    }

    public function test_queued_mail_without_a_request_can_be_restored(): void
    {
        $job = new SendQueuedServiceMail(new FormTemplateMail('Notice', '<p>Notice</p>'), 'client@example.test', null, 'Email sent.', 'Email failed');

        $restored = unserialize(serialize($job));

        $this->assertNull($restored->serviceRequest);
        $this->assertSame('Notice', $restored->mailable->renderedSubject);
    }
}
