<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\Test\EndomondoTracker;

use SportTrackerConnector\Core\Tracker\TrackerListWorkoutsResult;
use SportTrackerConnector\Core\Workout\Extension\HR;
use SportTrackerConnector\Core\Workout\SportMapperInterface;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\Workout;
use SportTrackerConnector\Endomondo\API\Workouts;
use SportTrackerConnector\Endomondo\EndomondoTracker;
use SportTrackerConnector\Endomondo\SportMapper;

/**
 * Test for the Endomondo tracker.
 */
class EndomondoTrackerTest extends \PHPUnit_Framework_TestCase
{
    public function testId()
    {
        static::assertSame('endomondo', EndomondoTracker::ID());
    }

    public function testDownloadWorkoutSuccess()
    {
        $workoutId = '123';
        $json = \GuzzleHttp\json_decode(
            file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'),
            true
        );

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::once())
            ->method('getWorkout')
            ->with($workoutId)
            ->will(self::returnValue($json));

        $endomondoTracker = new EndomondoTracker($workouts);
        $workout = $endomondoTracker->workout($workoutId);

        static::assertCount(1, $workout->tracks());

        $track = $workout->tracks()[0];
        static::assertSame(SportMapperInterface::CYCLING_SPORT, $track->sport());
        static::assertSame(128.045487, $track->length());
        static::assertEquals(new \DateTime('2014-06-04T18:05:32+0000'), $track->startDateTime());
        static::assertEquals(new \DateTime('2014-06-04T18:05:33+0000'), $track->endDateTime());
        static::assertSame(1, $track->duration()->totalSeconds());
        static::assertCount(2, $track->trackPoints());

        static::assertSame(129, $track->trackPoints()[0]->extension(HR::ID())->value());
        static::assertSame(130, $track->trackPoints()[1]->extension(HR::ID())->value());
    }

    public function testListWorkoutsWithEmptyList()
    {
        $startDate = new \DateTime('2016-01-01');
        $endDate = new \DateTime('2016-01-31');

        $workouts = $this->createMock(Workouts::class);
        $workouts
            ->expects(static::once())
            ->method('listWorkouts')
            ->with($startDate, $endDate)
            ->will(self::returnValue(array()));

        $endomondoTracker = new EndomondoTracker($workouts);
        $list = $endomondoTracker->workouts($startDate, $endDate);

        static::assertEmpty($list);
    }

    public function testListWorkoutsSuccess()
    {
        $startDate = new \DateTime('2016-01-01');
        $endDate = new \DateTime('2016-01-31');
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
        $list = $endomondoTracker->workouts($startDate, $endDate);

        static::assertEquals(
            array(
                new TrackerListWorkoutsResult(
                    '111111',
                    SportMapperInterface::RUNNING, new \DateTime('2014-07-24T18:45:00+0000')
                ),
                new TrackerListWorkoutsResult('222222',
                    SportMapperInterface::RUNNING,
                    new \DateTime('2014-07-24T16:55:00+0000')
                ),
                new TrackerListWorkoutsResult('333333',
                    SportMapperInterface::RUNNING,
                    new \DateTime('2014-07-22T18:32:00+0000')
                ),
            ),
            $list
        );
    }

    public function testUploadWorkout()
    {

        $workout = new Workout();
        $track1 = new Track([], SportMapperInterface::RUNNING);
        $track2 = new Track([], SportMapperInterface::SWIMMING);
        $workout->addTrack($track1);
        $workout->addTrack($track2);

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
        $post = $endomondoTracker->post($workout);

        static::assertTrue($post);
    }
}
