<?php

use App\Livewire\CakeAssistant;
use App\Models\AssistantMessage;
use Livewire\Livewire;

test('guests cannot open the assistant', function () {
    $this->get(route('assistant'))
        ->assertRedirect(route('login'));
});

test('customers can open the assistant', function () {
    $this->actingAs(customer())
        ->get(route('assistant'))
        ->assertOk()
        ->assertSee('Cake help, fast')
        ->assertSee('Your question')
        ->assertSee('WhatsApp handoff');
});

test('the assistant answers cake knowledge questions', function () {
    Livewire::actingAs(customer())
        ->test(CakeAssistant::class)
        ->set('message', 'What flavour options do you have?')
        ->call('ask')
        ->assertHasNoErrors()
        ->assertSee('Your question')
        ->assertSee('What flavour options do you have?')
        ->assertSee('<strong>Popular flavours</strong>', false)
        ->assertSee('<li>Vanilla sponge</li>', false);

    expect(AssistantMessage::query()->count())->toBe(2);
});

test('customers can ask a suggested cake question', function () {
    Livewire::actingAs(customer())
        ->test(CakeAssistant::class)
        ->call('askSuggestion', 'How many people does a 1kg cake serve?')
        ->assertHasNoErrors()
        ->assertSee('A 1kg cake serves about 8–10 people')
        ->assertSee('<strong>A 1kg cake serves about 8–10 people.</strong>', false);
});
