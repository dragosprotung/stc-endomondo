<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo;

use DateTime;
use DateTimeZone;
use SportTrackerConnector\Core\Tracker\AbstractTracker;
use SportTrackerConnector\Core\Tracker\TrackerListWorkoutsResult;
use SportTrackerConnector\Core\Workout\Extension\HR;
use SportTrackerConnector\Core\Workout\SportMapperInterface;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
use SportTrackerConnector\Core\Workout\Workout;
use SportTrackerConnector\Endomondo\API\Workouts;

/**
 * Endomondo tracker.
 */
class EndomondoTracker extends AbstractTracker
{
    /**
     * The Endomondo Workouts API.
     *
     * @var Workouts
     */
    protected $endomondoWorkouts;

    /**
     * @param Workouts $endomondoWorkouts
     */
    public function __construct(Workouts $endomondoWorkouts)
    {
        $this->endomondoWorkouts = $endomondoWorkouts;
    }

    /**
     * {@inheritdoc}
     */
    public function workout($idWorkout) : Workout
    {
        $json = $this->endomondoWorkouts->getWorkout($idWorkout);

        $workout = new Workout();
        $sport = $this->sportMapper()->sportFromCode((string)$json['sport']);
        $track = new Track(array(), $sport);

        if (array_key_exists('points', $json)) {
            foreach ($json['points'] as $point) {
                $trackPoint = new TrackPoint(
                    $point['lat'],
                    $point['lng'],
                    new DateTime($point['time'])
                );
                if (array_key_exists('alt', $point)) {
                    $trackPoint->setElevation($point['alt']);
                }
                if (array_key_exists('hr', $point)) {
                    $trackPoint->addExtension(new HR($point['hr']));
                }

                $track->addTrackPoint($trackPoint);
            }
        }

        $workout->addTrack($track);

        return $workout;
    }

    /**
     * {@inheritdoc}
     */
    public function workouts(DateTime $startDate, DateTime $endDate) : array
    {
        $list = array();
        $data = $this->endomondoWorkouts->listWorkouts($startDate, $endDate);
        foreach ($data as $workout) {
            $startDateTime = DateTime::createFromFormat(
                'Y-m-d H:i:s \U\T\C',
                $workout['start_time'],
                new DateTimeZone('UTC')
            );
            $list[] = new TrackerListWorkoutsResult(
                (string)$workout['id'],
                $this->sportMapper()->sportFromCode((string)$workout['sport']),
                $startDateTime
            );
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function post(Workout $workout) : bool
    {
        $workoutIds = array();
        foreach ($workout->tracks() as $track) {
            $sport = $this->sportMapper()->codeFromSport($track->sport());

            $workoutIds[] = $this->endomondoWorkouts->postTrack($track, $sport);
        }

        return count(array_filter($workoutIds)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function constructSportMapper() : SportMapperInterface
    {
        return new SportMapper();
    }

    /**
     * {@inheritdoc}
     */
    public static function ID() : string
    {
        return 'endomondo';
    }
}
