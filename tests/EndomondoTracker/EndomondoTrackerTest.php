<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\Test\EndomondoTracker;

use SportTrackerConnector\Core\Workout\Extension\HR;
use SportTrackerConnector\Core\Workout\SportMapperInterface;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
use SportTrackerConnector\Core\Workout\Workout;
use SportTrackerConnector\Core\Workout\WorkoutSummary;
use SportTrackerConnector\Endomondo\API\Workouts;
use SportTrackerConnector\Endomondo\EndomondoTracker;
use SportTrackerConnector\Endomondo\SportMapper;
use SportTrackerConnector\Endomondo\WorkoutId;

/**
 * Test for the Endomondo tracker.
 */
class EndomondoTrackerTest extends \PHPUnit_Framework_TestCase
{
    public function testId()
    {
        static::assertSame('endomondo', EndomondoTracker::ID());
    }

    public function testListWithEmptyList()
    {
        $startDate = new \DateTimeImmutable('2016-01-01');
        $endDate = new \DateTimeImmutable('2016-01-31');

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::once())
            ->method('listWorkouts')
            ->with($startDate, $endDate)
            ->will(self::returnValue(array()));

        $endomondoTracker = new EndomondoTracker($workouts);
        $list = $endomondoTracker->list($startDate, $endDate);

        static::assertEmpty($list);
    }

    public function testListSuccess()
    {
        $startDate = new \DateTimeImmutable('2016-01-01');
        $endDate = new \DateTimeImmutable('2016-01-31');
        $json = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'),
            true
        );

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::once())
            ->method('listWorkouts')
            ->with($startDate, $endDate)
            ->will(self::returnValue($json));

        $endomondoTracker = new EndomondoTracker($workouts);
        $list = $endomondoTracker->list($startDate, $endDate);

        static::assertEquals(
            array(
                new WorkoutSummary(
                    new WorkoutId('111111'),
                    SportMapperInterface::RUNNING,
                    new \DateTimeImmutable('2014-07-24T18:45:00+0000')
                ),
                new WorkoutSummary(
                    new WorkoutId('222222'),
                    SportMapperInterface::RUNNING,
                    new \DateTimeImmutable('2014-07-24T16:55:00+0000')
                ),
                new WorkoutSummary(
                    new WorkoutId('333333'),
                    SportMapperInterface::RUNNING,
                    new \DateTimeImmutable('2014-07-22T18:32:00+0000')
                ),
            ),
            $list
        );
    }

    public function testWorkoutSuccess()
    {
        $workoutId = new WorkoutId('123');
        $json = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'),
            true
        );

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::once())
            ->method('getWorkout')
            ->with($workoutId->toString())
            ->will(self::returnValue($json));

        $endomondoTracker = new EndomondoTracker($workouts);
        $workout = $endomondoTracker->workout($workoutId);

        static::assertCount(1, $workout->tracks());

        $track = $workout->tracks()[0];
        static::assertSame(SportMapperInterface::CYCLING_SPORT, $track->sport());
        static::assertSame(128.045487, $track->length());
        static::assertEquals(new \DateTimeImmutable('2014-06-04T18:05:32+0000'), $track->startDateTime());
        static::assertEquals(new \DateTimeImmutable('2014-06-04T18:05:33+0000'), $track->endDateTime());
        static::assertSame(1, $track->duration()->totalSeconds());
        static::assertCount(2, $track->trackPoints());

        static::assertSame(129, $track->trackPoints()[0]->extension(HR::ID())->value());
        static::assertSame(130, $track->trackPoints()[1]->extension(HR::ID())->value());
    }

    public function testWorkoutsSuccess()
    {
        $startDate = new \DateTimeImmutable('2016-01-01');
        $endDate = new \DateTimeImmutable('2016-01-31');
        $json = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-list.json'),
            true
        );

        $workouts = $this->createPartialMock(Workouts::class, array('listWorkouts', 'getWorkout'));
        $workouts
            ->expects(static::once())
            ->method('listWorkouts')
            ->with($startDate, $endDate)
            ->willReturn($json);

        $jsonWorkout111111 = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-workout-111111.json'),
            true
        );
        $jsonWorkout222222 = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-workout-222222.json'),
            true
        );
        $jsonWorkout333333 = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-workout-333333.json'),
            true
        );
        $workouts
            ->expects(self::at(1))
            ->method('getWorkout')
            ->willReturn($jsonWorkout111111);
        $workouts
            ->expects(self::at(2))
            ->method('getWorkout')
            ->willReturn($jsonWorkout222222);
        $workouts
            ->expects(self::at(3))
            ->method('getWorkout')
            ->willReturn($jsonWorkout333333);

        $endomondoTracker = new EndomondoTracker($workouts);
        $list = $endomondoTracker->workouts($startDate, $endDate);

        $track1 = new Track(
            [
                TrackPoint::with(
                    53.551075,
                    9.993672,
                    new \DateTimeImmutable('2014-06-0418:05:32UTC'),
                    null,
                    [HR::fromValue(129)]
                ),
                TrackPoint::with(
                    53.550085,
                    9.992682,
                    new \DateTimeImmutable('2014-06-0418:05:33UTC'),
                    null,
                    [HR::fromValue(130)]
                ),
            ],
            SportMapperInterface::RUNNING
        );
        $track2 = new Track(
            [
                TrackPoint::with(
                    53.551075,
                    9.993672,
                    new \DateTimeImmutable('2014-06-0418:05:32UTC')
                )
            ],
            SportMapperInterface::OTHER);
        $track3 = new Track([], SportMapperInterface::CYCLING_SPORT);
        static::assertEquals(
            array(
                new Workout([$track1]),
                new Workout([$track2]),
                new Workout([$track3]),
            ),
            $list
        );
    }

    public function testUploadWorkout()
    {
        $track1 = new Track([], SportMapperInterface::RUNNING);
        $track2 = new Track([], SportMapperInterface::SWIMMING);
        $workout = new Workout([$track1, $track2]);

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::at(0))
            ->method('postTrack')
            ->with($track1, SportMapper::SPORT_RUNNING)
            ->will(self::returnValue('1111111'));
        $workouts
            ->expects(static::at(1))
            ->method('postTrack')
            ->with($track2, SportMapper::SPORT_SWIMMING)
            ->will(self::returnValue('2222222'));
        $endomondoTracker = new EndomondoTracker($workouts);
        $post = $endomondoTracker->save($workout);

        static::assertTrue($post);
    }
}
