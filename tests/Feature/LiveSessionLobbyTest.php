<?php

namespace Tests\Feature;

use App\Enums\LiveSessionStatus;
use App\Models\LiveSession;
use App\Models\LiveSessionParticipant;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LiveSessionLobbyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_educator_can_open_a_lobby_for_an_owned_published_quiz(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();

        $response = $this->actingAs($educator)->post(
            route('educator.quizzes.live-sessions.store', $quiz),
        );

        $liveSession = LiveSession::query()->sole();
        $response->assertRedirect(route('educator.live-sessions.show', $liveSession))
            ->assertSessionHas('status', 'Live lobby is ready. Share the join code with your learners.');
        $this->assertSame($quiz->getKey(), $liveSession->quiz_id);
        $this->assertSame($educator->getKey(), $liveSession->host_id);
        $this->assertSame(LiveSessionStatus::Waiting, $liveSession->status);
        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{6}$/', $liveSession->code);
    }

    public function test_opening_the_same_quiz_reuses_its_active_lobby(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();

        $this->actingAs($educator)->post(route('educator.quizzes.live-sessions.store', $quiz));
        $this->actingAs($educator)->post(route('educator.quizzes.live-sessions.store', $quiz));

        $this->assertSame(1, LiveSession::query()->count());
    }

    public function test_draft_quiz_cannot_open_a_live_lobby(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->post(route('educator.quizzes.live-sessions.store', $quiz))
            ->assertSessionHasErrors([
                'quiz' => 'Only a currently open quiz can be used for a live session.',
            ]);

        $this->assertSame(0, LiveSession::query()->count());
    }

    public function test_scheduled_quiz_can_open_a_live_lobby_after_its_start_time(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->scheduled()->for($educator, 'educator')->create([
            'opens_at' => now()->subMinute(),
            'closes_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($educator)
            ->post(route('educator.quizzes.live-sessions.store', $quiz));

        $liveSession = LiveSession::query()->sole();
        $response->assertRedirect(route('educator.live-sessions.show', $liveSession));
        $this->actingAs($educator)
            ->get(route('educator.quizzes.show', $quiz))
            ->assertSee('Open live lobby');
    }

    public function test_scheduled_quiz_cannot_open_a_live_lobby_before_its_start_time(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->scheduled()->for($educator, 'educator')->create();

        $this->actingAs($educator)
            ->post(route('educator.quizzes.live-sessions.store', $quiz))
            ->assertSessionHasErrors('quiz');

        $this->assertDatabaseCount('live_sessions', 0);
    }

    public function test_other_educator_cannot_open_or_view_an_owned_quiz_lobby(): void
    {
        $owner = User::factory()->educator()->create();
        $otherEducator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($owner, 'educator')->create();
        $liveSession = LiveSession::factory()->for($quiz)->for($owner, 'host')->create();

        $this->actingAs($otherEducator)
            ->post(route('educator.quizzes.live-sessions.store', $quiz))
            ->assertForbidden();
        $this->actingAs($otherEducator)
            ->get(route('educator.live-sessions.show', $liveSession))
            ->assertForbidden();
    }

    public function test_learner_can_join_with_a_normalized_code_and_see_the_lobby(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create(['name' => 'Amina Learner']);
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create(['title' => 'Science sprint']);
        $liveSession = LiveSession::factory()->for($quiz)->for($educator, 'host')->create(['code' => 'AB23CD']);

        $response = $this->actingAs($learner)->post(
            route('learner.live-sessions.store'),
            ['code' => ' ab 23cd '],
        );

        $response->assertRedirect(route('learner.live-sessions.show', $liveSession))
            ->assertSessionHas('status', 'You joined the lobby.');
        $this->assertDatabaseHas('live_session_participants', [
            'live_session_id' => $liveSession->getKey(),
            'learner_id' => $learner->getKey(),
            'status' => 'joined',
        ]);
        $this->actingAs($learner)
            ->get(route('learner.live-sessions.show', $liveSession))
            ->assertSee('Science sprint')
            ->assertSee('Ready in the lobby');
    }

    public function test_rejoining_does_not_duplicate_the_participant(): void
    {
        $learner = User::factory()->learner()->create();
        $liveSession = LiveSession::factory()->create(['code' => 'XY23ZA']);

        $this->actingAs($learner)->post(route('learner.live-sessions.store'), ['code' => 'XY23ZA']);
        $joinedAt = LiveSessionParticipant::query()->sole()->joined_at;
        $this->travel(30)->seconds();
        $this->actingAs($learner)->post(route('learner.live-sessions.store'), ['code' => 'XY23ZA']);

        $participant = LiveSessionParticipant::query()->sole();
        $this->assertSame(1, LiveSessionParticipant::query()->count());
        $this->assertTrue($joinedAt->equalTo($participant->joined_at));
        $this->assertTrue($participant->last_seen_at->greaterThan($joinedAt));
    }

    public function test_learner_heartbeat_updates_presence_without_reloading_the_page(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $liveSession = LiveSession::factory()->for($quiz)->for($educator, 'host')->create();
        $participant = LiveSessionParticipant::factory()->for($liveSession)->for($learner, 'learner')->create([
            'last_seen_at' => now()->subSeconds(30),
        ]);

        $this->actingAs($educator)
            ->get(route('educator.live-sessions.show', $liveSession))
            ->assertSee('Away');
        $before = $this->actingAs($learner)
            ->getJson(route('live-sessions.state', $liveSession))
            ->json('version');
        $this->assertTrue($participant->fresh()->last_seen_at->lessThan(now()->subSeconds(15)));

        $response = $this->actingAs($learner)
            ->postJson(route('live-sessions.state', $liveSession));

        $response->assertOk();
        $this->assertNotSame($before, $response->json('version'));
        $this->assertTrue($participant->fresh()->last_seen_at->greaterThan(now()->subSeconds(5)));
        $this->actingAs($educator)
            ->get(route('educator.live-sessions.show', $liveSession))
            ->assertSee('Online');
    }

    public function test_unjoined_learner_cannot_send_a_live_heartbeat(): void
    {
        $learner = User::factory()->learner()->create();
        $liveSession = LiveSession::factory()->create();

        $this->actingAs($learner)
            ->postJson(route('live-sessions.state', $liveSession))
            ->assertForbidden();

        $this->assertDatabaseCount('live_session_participants', 0);
    }

    public function test_live_state_changes_when_online_learners_swap_without_changing_the_online_count(): void
    {
        $educator = User::factory()->educator()->create();
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $liveSession = LiveSession::factory()->for($quiz)->for($educator, 'host')->create();
        $firstParticipant = LiveSessionParticipant::factory()->for($liveSession)->create([
            'last_seen_at' => now(),
        ]);
        $secondParticipant = LiveSessionParticipant::factory()->for($liveSession)->create([
            'last_seen_at' => now()->subSeconds(30),
        ]);

        $before = $this->actingAs($educator)
            ->getJson(route('live-sessions.state', $liveSession))
            ->json('version');

        $firstParticipant->update(['last_seen_at' => now()->subSeconds(30)]);
        $secondParticipant->update(['last_seen_at' => now()]);

        $after = $this->actingAs($educator)
            ->getJson(route('live-sessions.state', $liveSession))
            ->json('version');

        $this->assertNotSame($before, $after);
    }

    public function test_invalid_or_closed_code_does_not_create_a_participant(): void
    {
        $learner = User::factory()->learner()->create();
        LiveSession::factory()->create([
            'code' => 'CD45EF',
            'status' => LiveSessionStatus::Completed,
        ]);

        $this->actingAs($learner)
            ->post(route('learner.live-sessions.store'), ['code' => 'CD45EF'])
            ->assertSessionHasErrors([
                'code' => 'This live session code is invalid or no longer accepting learners.',
            ]);
        $this->actingAs($learner)
            ->post(route('learner.live-sessions.store'), ['code' => 'GH67JK'])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, LiveSessionParticipant::query()->count());
    }

    public function test_learner_cannot_view_a_lobby_without_joining_it(): void
    {
        $learner = User::factory()->learner()->create();
        $liveSession = LiveSession::factory()->create();

        $this->actingAs($learner)
            ->get(route('learner.live-sessions.show', $liveSession))
            ->assertForbidden();
    }

    public function test_host_lobby_escapes_learner_names(): void
    {
        $educator = User::factory()->educator()->create();
        $learner = User::factory()->learner()->create([
            'name' => '<script>alert("joined")</script>',
        ]);
        $quiz = Quiz::factory()->published()->for($educator, 'educator')->create();
        $liveSession = LiveSession::factory()->for($quiz)->for($educator, 'host')->create();
        LiveSessionParticipant::factory()
            ->for($liveSession)
            ->for($learner, 'learner')
            ->create();

        $response = $this->actingAs($educator)
            ->get(route('educator.live-sessions.show', $liveSession));

        $response->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("joined")</script>', false);
    }

    public function test_join_endpoint_is_rate_limited(): void
    {
        $learner = User::factory()->learner()->create();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->actingAs($learner)
                ->post(route('learner.live-sessions.store'), ['code' => 'AB23CD'])
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($learner)
            ->post(route('learner.live-sessions.store'), ['code' => 'AB23CD'])
            ->assertTooManyRequests();
    }
}
