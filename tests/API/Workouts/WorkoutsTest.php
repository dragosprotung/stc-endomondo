<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\Test\API\Workouts;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
use SportTrackerConnector\Core\Workout\Workout;
use SportTrackerConnector\Endomondo\API\Authentication;
use SportTrackerConnector\Endomondo\API\Exception\BadResponseException;
use SportTrackerConnector\Endomondo\API\Workouts;
use SportTrackerConnector\Endomondo\SportMapper;

/**
 * Test case for Workouts.
 */
class WorkoutsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWorkoutThrowsExceptionOnInvalidAuthentication()
    {
        $authentication = Authentication::fromToken('wrong_token');

        $mockHandler = new MockHandler(
            [
                new Response(200, [], '{"error":{"type":"AUTH_FAILED"}}')
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $this->expectException(BadResponseException::class);

        $workouts = new Workouts($authentication, $client);
        $workouts->getWorkout('123456');
    }

    public function testGetWorkoutSuccess()
    {
        $authentication = Authentication::fromToken('token');

        $responseFixtureFile = __DIR__ . '/Fixtures/' . $this->getName() . '.json';
        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents($responseFixtureFile))
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $workouts = new Workouts($authentication, $client);
        $actual = $workouts->getWorkout('123456');
        static::assertJsonStringEqualsJsonFile($responseFixtureFile, json_encode($actual));
    }

    public function testListWorkoutsWithNoMoreData()
    {
        $authentication = Authentication::fromToken('token');

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'))
            ]
        );
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $workouts = new Workouts($authentication, $client);
        $actual = $workouts->listWorkouts(new \DateTime('-2 weeks'), new \DateTime('now'));
        static::assertJsonStringEqualsJsonFile(__DIR__ . '/Expected/' . $this->getName() . '.json',
            json_encode($actual));
    }

    public function testPostTrackSuccess()
    {
        $trackPoints = array_merge($this->getTrackPointMocks(150, true), $this->getTrackPointMocks(150));

        $track = new Track($trackPoints);

        $workout = $this->createMock(Workout::class);
        $workout->expects(static::any())->method('getTracks')->will(static::returnValue(array($track)));

        $authentication = Authentication::fromToken('token');

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt'))
            ]
        );
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $workouts = new Workouts($authentication, $client);
        $workoutId = $workouts->postTrack($track, SportMapper::RUNNING);

        static::assertSame('123456789', $workoutId);
    }

    /**
     * Get a number of track point mocks.
     *
     * @param integer $number Number of mocks to get.
     * @param boolean $distance Flag if distance should be present in the track point.
     * @return \SportTrackerConnector\Core\Workout\TrackPoint[]
     */
    private function getTrackPointMocks(int $number, bool $distance = false)
    {
        $mocks = array();
        for ($i = 0; $i < $number; $i++) {
            $mocks[] = $this->getTrackPointMock($distance ? $i : null);
        }

        return $mocks;
    }

    /**
     * Get a track point mock.
     *
     * @param integer $distance The distance for the point.
     * @return \SportTrackerConnector\Core\Workout\TrackPoint
     */
    private function getTrackPointMock($distance = null)
    {
        $trackPointMock = $this->createMock(TrackPoint::class);
        $trackPointMock
            ->expects(static::any())
            ->method('getDateTime')
            ->will(static::returnValue(new \DateTime()));
        $trackPointMock
            ->expects(static::any())
            ->method('getLatitude')
            ->will(static::returnValue(random_int(5353479, 5353579) / 100000));
        $trackPointMock
            ->expects(static::any())
            ->method('getLongitude')
            ->will(static::returnValue(random_int(10000000, 10100000) / 1000000));
        $trackPointMock
            ->expects(static::any())
            ->method('getElevation')
            ->will(static::returnValue(random_int(0, 100)));
        if ($distance !== null) {
            $trackPointMock->expects(static::any())->method('hasDistance')->will(static::returnValue(true));
            $trackPointMock->expects(static::any())->method('getDistance')->will(static::returnValue($distance));
        }
        $trackPointMock->expects(static::any())->method('hasExtension')->with('HR')->will(static::returnValue(false));

        return $trackPointMock;
    }
}
