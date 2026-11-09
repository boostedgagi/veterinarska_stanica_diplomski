<?php

namespace App\Tests\Functional;

/** A vet goes on call, an owner opens a chat and writes, the vet reads it. */
class OnCallChatFlowTest extends ApiTestCase
{
    public function testOwnerChatsWithAnOnCallVet(): void
    {
        $vet = $this->vet();
        $owner = $this->owner();
        $stranger = $this->owner();

        [$status, $onCall] = $this->api($vet, 'POST', '/on_call', ['vet' => $vet->getId()]);
        self::assertSame(201, $status);

        [, $active] = $this->api(null, 'GET', '/active_vets');
        self::assertContains($vet->getId(), self::ids($active, 'activeVetId'));

        [$status, $chat] = $this->api(null, 'POST', '/initialize_chat_id');
        self::assertSame(200, $status);
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $chat['chatId']);

        [$status] = $this->api($owner, 'POST', '/message', [
            'sender' => (string) $owner->getId(), 'receiver' => (string) $vet->getId(), 'content' => 'Is my dog OK?', 'chatId' => $chat['chatId'],
        ]);
        self::assertSame(200, $status);

        // was unreachable: the route took an int, chat ids are 64 hex chars
        [$status, $messages] = $this->api($vet, 'GET', '/chat/' . $chat['chatId']);
        self::assertSame(200, $status);
        self::assertCount(1, $messages);
        self::assertSame('Is my dog OK?', $messages[0]['content']);

        self::assertSame(403, $this->api($stranger, 'GET', '/chat/' . $chat['chatId'])[0]);
        self::assertSame(201, $this->api($vet, 'POST', '/message/' . $messages[0]['id'] . '/seen')[0]);

        [$status] = $this->api($vet, 'PUT', '/on_call/' . $onCall['id'], ['finishedAt' => '2020-01-01T00:00']);
        self::assertSame(201, $status);
    }
}
