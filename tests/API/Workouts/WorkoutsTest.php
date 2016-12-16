<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\Test\API\Workouts;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
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
        $authentication = Authentication::withToken('wrong_token');

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
        $authentication = Authentication::withToken('token');

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
        $authentication = Authentication::withToken('token');

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'))
            ]
        );
        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        $workouts = new Workouts($authentication, $client);
        $actual = $workouts->listWorkouts(new \DateTimeImmutable('-2 weeks'), new \DateTimeImmutable('now'));
        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/Expected/' . $this->getName() . '.json',
            json_encode($actual)
        );
    }

    public function testPostTrackSuccess()
    {
        $trackPoints = array_merge($this->getTrackPointMocks(150), $this->getTrackPointMocks(140));
        $track = new Track($trackPoints);

        $authentication = Authentication::withToken('token');

        $container = array();
        $history = Middleware::history($container);

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt')),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.txt'))
            ]
        );
        $handler = HandlerStack::create($mockHandler);
        $handler->push($history);

        $client = new Client(['handler' => $handler]);

        $workouts = new Workouts($authentication, $client);
        $workoutId = $workouts->postTrack($track, SportMapper::RUNNING);

        foreach ($container as $i => $transaction) {
            /** @var RequestInterface $request */
            $request = $transaction['request'];
            static::assertSame('POST', $request->getMethod());

            static::assertStringEqualsFile(
                __DIR__ . '/Expected/' . $this->getName() . '-' . $i . '.txt',
                gzdecode($request->getBody()->getContents())
            );
        }

        static::assertSame('123456789', $workoutId);
    }

    /**
     * Get a number of track point mocks.
     *
     * @param integer $number Number of mocks to get.
     * @return TrackPoint[]
     */
    private function getTrackPointMocks(int $number)
    {
        $mocks = array();
        for ($i = 0; $i < $number; $i++) {
            $mocks[] = $this->getTrackPointMock();
        }

        return $mocks;
    }

    /**
     * Get a track point mock.
     *
     * @return TrackPoint
     */
    private function getTrackPointMock()
    {
        static $call = 1;

        $dateTime = new \DateTimeImmutable('2016-01-01 00:00:00');
        $dateTime = $dateTime->add(\DateInterval::createFromDateString('+' . $call . ' seconds'));

        $latitude = 5352479;
        $longitude = 10000000;
        $elevation = $call % 1000;

        $trackPoint = TrackPoint::with(
            ($latitude + $call) / 100000,
            ($longitude + $call) / 1000000,
            $dateTime,
            $elevation
        );

        $call++;

        return $trackPoint;
    }
}
