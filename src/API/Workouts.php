<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo\API;

use GuzzleHttp\Client;
use SportTrackerConnector\Core\Workout\Extension\HR;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
use SportTrackerConnector\Endomondo\API\Exception\BadResponseException;

/**
 * Class for working with Endomondo API.
 */
class Workouts
{
    const URL_BASE = 'https://api.mobile.endomondo.com/mobile';
    const URL_WORKOUTS = 'https://api.mobile.endomondo.com/mobile/api/workouts';
    const URL_WORKOUT_GET = 'https://api.mobile.endomondo.com/mobile/api/workout/get';
    const URL_WORKOUT_POST = 'https://api.mobile.endomondo.com/mobile/api/workout/post';
    const URL_TRACK = 'https://api.mobile.endomondo.com/mobile/track';
    const URL_FRIENDS = 'https://api.mobile.endomondo.com/mobile/friends';

    const INSTRUCTION_PAUSE = 0;
    const INSTRUCTION_RESUME = 1;
    const INSTRUCTION_START = 2;
    const INSTRUCTION_STOP = 3;
    const INSTRUCTION_NONE = 4;
    const INSTRUCTION_GPS_OFF = 5;
    const INSTRUCTION_LAP = 6;

    /**
     * Endomondo authentication token.
     *
     * @var string
     */
    protected $authentication;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Authentication $authentication
     * @param Client $client
     */
    public function __construct(Authentication $authentication, Client $client)
    {
        $this->authentication = $authentication;
        $this->client = $client;
    }

    /**
     * Get the details of a workout.
     *
     * Possible fields when getting the workout:
     *  device,simple,basic,motivation,interval,hr_zones,weather,polyline_encoded_small,points,lcp_count,tagged_users,pictures,feed
     *
     * @param string $idWorkout The ID of the workout.
     * @return array
     * @throws \RuntimeException
     */
    public function getWorkout(string $idWorkout) : array
    {
        $response = $this
            ->client
            ->get(
                self::URL_WORKOUT_GET,
                array(
                    'query' => array(
                        'authToken' => $this->authentication->token(),
                        'fields' => 'device,simple,basic,motivation,interval,weather,polyline_encoded_small,points,lcp_count,tagged_users,pictures',
                        'workoutId' => $idWorkout
                    )
                )
            );


        $json = \GuzzleHttp\json_decode($response->getBody(), true);
        if (isset($json['error'])) {
            throw new BadResponseException('Endomondo returned an unexpected error :"' . json_encode($json['error']));
        }

        return $json;
    }

    /**
     * Get a list of workouts in a date interval.
     *
     * @param \DateTime $startDate The start date for the workouts.
     * @param \DateTime $endDate The end date for the workouts.
     * @return array
     */
    public function listWorkouts(\DateTime $startDate, \DateTime $endDate) : array
    {
        $response = $this
            ->client
            ->get(
                self::URL_WORKOUTS,
                array(
                    'query' => array(
                        'authToken' => $this->authentication->token(),
                        'fields' => 'simple',
                        'maxResults' => 100000, // Be lazy and fetch everything in one request.
                        'after' => $startDate->format('Y-m-d H:i:s \U\T\C'),
                        'before' => $endDate->format('Y-m-d H:i:s \U\T\C')
                    )
                )
            );

        $json = \GuzzleHttp\json_decode($response->getBody(), true);

        return $json['data'];
    }

    /**
     * Post one workout track to Endomondo.
     *
     * @param Track $track
     * @param string $sport
     * @return null|string
     */
    public function postTrack(Track $track, string $sport)
    {
        $deviceWorkoutId = $this->generateDeviceWorkoutId();
        $duration = $track->duration()->totalSeconds();

        $workoutId = null;
        $previousPoint = null;
        $distance = 0;
        $speed = 0;
        // Split in chunks of 100 points like the mobile app.
        foreach (array_chunk($track->trackPoints(), 100) as $trackPoints) {
            $data = array();
            /** @var TrackPoint[] $trackPoints */
            foreach ($trackPoints as $trackPoint) {
                if ($trackPoint->hasDistance() === true) {
                    $distance = $trackPoint->distance();
                } elseif ($previousPoint !== null) {
                    $distance += $trackPoint->distanceFromPoint($previousPoint);
                }
                if ($previousPoint !== null) {
                    $speed = $trackPoint->speed($previousPoint);
                }

                $data[] = $this->flattenTrackPoint($trackPoint, $distance, $speed);

                $previousPoint = $trackPoint;
            }

            $this->postWorkoutData($deviceWorkoutId, $sport, $duration, $data);
        }

        // End of workout data.
        $data = $this->flattenEndWorkoutTrackPoint($track, $speed);
        $workoutId = $this->postWorkoutData($deviceWorkoutId, $sport, $duration, array($data));

        return $workoutId;
    }

    /**
     * Post the workout end data.
     *
     * @param Track $track The track.
     * @param float $speed The speed for the last point.
     * @return string The workout ID.
     */
    private function flattenEndWorkoutTrackPoint(Track $track, $speed)
    {
        $endDateTime = clone $track->endDateTime();
        $endDateTime->setTimezone(new \DateTimeZone('UTC'));
        $distance = $track->length();
        $lastTrackPoint = $track->lastTrackPoint();

        $totalAscent = $lastTrackPoint->elevation(); // TODO Compute it from the track, this is not correct.

        return $this->formatEndomondoTrackPoint(
            $endDateTime,
            self::INSTRUCTION_STOP,
            $lastTrackPoint->latitude(),
            $lastTrackPoint->longitude(),
            $distance,
            $speed,
            $totalAscent,
            $lastTrackPoint->hasExtension(HR::ID()) ? $lastTrackPoint->extension(HR::ID())->value() : ''
        );
    }

    /**
     * Post workout data chunk.
     *
     * @param string $deviceWorkoutId The workout ID in progress of the device.
     * @param string $sport The sport.
     * @param integer $duration The duration in seconds.
     * @param array $data The data points to post.
     * @return string The workout ID.
     * @throws \RuntimeException
     */
    private function postWorkoutData($deviceWorkoutId, $sport, $duration, array $data) : string
    {
        $body = \GuzzleHttp\Psr7\stream_for(gzencode(implode("\n", $data)));

        $response = $this
            ->client
            ->post(
                self::URL_TRACK,
                array(
                    'query' => array(
                        'authToken' => $this->authentication->token(),
                        'gzip' => 'true',
                        'workoutId' => $deviceWorkoutId,
                        'sport' => $sport,
                        'duration' => $duration,
                        'audioMessage' => 'false',
                        'goalType' => 'BASIC',
                        'extendedResponse' => 'true'
                    ),
                    'headers' => array(
                        'Content-Type' => 'application/octet-stream'
                    ),
                    'body' => $body
                )
            );

        $responseBody = $response->getBody()->getContents();
        $response = parse_ini_string($responseBody);

        if (array_key_exists('workout.id', $response)) {
            return $response['workout.id'];
        }

        throw new \RuntimeException('Unexpected response from Endomondo. Data may be partially uploaded. Response was: ' . $responseBody);
    }

    /**
     * Flatten a track point to be posted on Endomondo.
     *
     * @param TrackPoint $trackPoint The track point to flatten.
     * @param float $distance The total distance the point in meters.
     * @param float $speed The speed the point in km/h from the previous point.
     * @return string
     */
    private function flattenTrackPoint(TrackPoint $trackPoint, $distance, $speed) : string
    {
        $dateTime = clone $trackPoint->dateTime();
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return $this->formatEndomondoTrackPoint(
            $dateTime,
            self::INSTRUCTION_START,
            $trackPoint->latitude(),
            $trackPoint->longitude(),
            $distance,
            $speed,
            $trackPoint->elevation(),
            $trackPoint->hasExtension(HR::ID()) ? $trackPoint->extension(HR::ID())->value() : ''
        );
    }

    /**
     * Format a point to send to Endomondo when posting a new workout.
     *
     * Type:
     *  0 - pause
     *  1 - running ?
     *  2 - running
     *  3 - stop
     *
     * @param \DateTime $dateTime
     * @param integer $type The post type (0-6). Don't know what they mean.
     * @param string $lat The latitude of the point.
     * @param string $lon The longitude of the point.
     * @param string $distance The distance in meters.
     * @param string $speed The speed in km/h.
     * @param string $elevation The elevation
     * @param string $heartRate The heart rate.
     * @param string $cadence The cadence (in rpm).
     * @return string
     */
    private function formatEndomondoTrackPoint(
        \DateTime $dateTime,
        $type,
        $lat = null,
        $lon = null,
        $distance = null,
        $speed = null,
        $elevation = null,
        $heartRate = null,
        $cadence = null
    ) : string
    {
        $dateTime = clone $dateTime;
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return sprintf(
            '%s;%s;%s;%s;%s;%s;%s;%s;%s;',
            $dateTime->format('Y-m-d H:i:s \U\T\C'),
            $type,
            $lat,
            $lon,
            $distance / 1000,
            $speed,
            $elevation,
            $heartRate,
            $cadence
        );
    }

    /**
     * Generate a big number of specified length.
     *
     * @return string
     */
    private function generateDeviceWorkoutId()
    {
        $randNumber = '-';

        for ($i = 0; $i < 19; $i++) {
            $randNumber .= random_int(0, 9);
        }

        return $randNumber;
    }
}
