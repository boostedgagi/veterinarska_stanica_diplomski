<?php

namespace App\Tests\Functional;

use App\Entity\HealthRecord;
use App\Factory\ExaminationFactory;

/** Booking an appointment, the vet's availability, cancelling. */
class AppointmentFlowTest extends ApiTestCase
{
    /** @return list<mixed> ids of the vets free in [from, to) */
    private function freeVets(string $from, string $to): array
    {
        [$status, $body] = $this->api($this->owner(), 'GET', '/vets/free?' . http_build_query(['from' => $from, 'to' => $to]));
        self::assertSame(200, $status);

        return self::ids($body);
    }

    public function testOwnerBooksAnAppointmentAndIsNotified(): void
    {
        $owner = $this->owner();
        $vet = $this->vet();
        $pet = $this->pet($owner);

        [$status, $body] = $this->api($owner, 'POST', '/health_record', [
            'vet' => $vet->getId(), 'pet' => $pet->getId(), 'examination' => ExaminationFactory::createOne()->getId(),
            'startedAt' => '2027-03-01T10:00:00', 'finishedAt' => '2027-03-01T11:00:00', 'status' => 'waiting',
        ]);

        self::assertSame(201, $status);
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', $owner->getEmail());

        $record = $this->reload(HealthRecord::class, $body['id']);
        self::assertSame($vet->getId(), $record->getVet()->getId());
        self::assertFalse($record->isMadeByVet());

        [, $mine] = $this->api($owner, 'GET', '/user/' . $owner->getId() . '/health_records');
        self::assertContains($body['id'], self::ids($mine));
        [, $vets] = $this->api($vet, 'GET', '/vet/' . $vet->getId() . '/health_record');
        self::assertContains($body['id'], self::ids($vets['items']));
    }

    public function testFreeVetsRespectOverlappingAppointments(): void
    {
        $vet = $this->vet();
        $this->appointment($vet, $this->pet($this->owner()), '2027-03-01 10:00', '2027-03-01 11:00');

        self::assertNotContains($vet->getId(), $this->freeVets('2027-03-01 10:15', '2027-03-01 10:45'), 'inside the appointment (was shown free)');
        self::assertNotContains($vet->getId(), $this->freeVets('2027-03-01 09:30', '2027-03-01 12:00'), 'around the appointment');
        self::assertNotContains($vet->getId(), $this->freeVets('2027-03-01 10:30', '2027-03-01 11:30'), 'overlapping the end');
        self::assertContains($vet->getId(), $this->freeVets('2027-03-01 11:00', '2027-03-01 12:00'), 'right after it');
        self::assertContains($vet->getId(), $this->freeVets('2027-03-02 10:00', '2027-03-02 11:00'), 'another day');
    }

    public function testCancelledAppointmentFreesTheVet(): void
    {
        $vet = $this->vet();
        $record = $this->appointment($vet, $this->pet($this->owner()), '2027-03-01 10:00', '2027-03-01 11:00');

        // only the status is sent (what the frontend's status change does); was a 500
        [$status, $body] = $this->api($vet, 'PUT', '/health_record/' . $record->getId(), ['status' => 'canceled']);
        self::assertSame(201, $status);
        self::assertSame('canceled', $body['status']);
        self::assertNotNull($this->reload(HealthRecord::class, $record->getId())->getStartedAt(), 'other fields are kept');

        self::assertContains($vet->getId(), $this->freeVets('2027-03-01 10:15', '2027-03-01 10:45'));
    }

    public function testFreeVetsNeedAValidRange(): void
    {
        foreach (['', '?from=2027-03-01%2010:00', '?from=2027-03-01%2011:00&to=2027-03-01%2010:00', '?from=nope&to=2027-03-01%2010:00'] as $query) {
            self::assertSame(400, $this->api($this->owner(), 'GET', '/vets/free' . $query)[0], $query);
        }
    }
}
