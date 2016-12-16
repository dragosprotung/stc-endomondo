<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo;

use SportTrackerConnector\Core\Tracker\TrackerInterface;
use SportTrackerConnector\Core\Workout\Extension\HR;
use SportTrackerConnector\Core\Workout\SportMapperInterface;
use SportTrackerConnector\Core\Workout\Track;
use SportTrackerConnector\Core\Workout\TrackPoint;
use SportTrackerConnector\Core\Workout\Workout;
use SportTrackerConnector\Core\Workout\WorkoutIdInterface;
use SportTrackerConnector\Core\Workout\WorkoutSummary;
use SportTrackerConnector\Endomondo\API\Workouts;

/**
 * Endomondo tracker.
 */
final class EndomondoTracker implements TrackerInterface
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
    public function list(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $list = array();
        $data = $this->endomondoWorkouts->listWorkouts($startDate, $endDate);
        foreach ($data as $workout) {
            $startDateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s \U\T\C',
                $workout['start_time'],
                new \DateTimeZone('UTC')
            );
            $list[] = new WorkoutSummary(
                new WorkoutId((string)$workout['id']),
                $this->sportMapper()->sportFromCode((string)$workout['sport']),
                $startDateTime
            );
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function workout(WorkoutIdInterface $idWorkout): Workout
    {
        $json = $this->endomondoWorkouts->getWorkout($idWorkout->toString());

        $trackPoints = [];
        if (array_key_exists('points', $json)) {
            foreach ($json['points'] as $point) {
                $elevation = null;
                if (array_key_exists('alt', $point)) {
                    $elevation = $point['alt'];
                }
                $extensions = [];
                if (array_key_exists('hr', $point)) {
                    $extensions[] = HR::fromValue($point['hr']);
                }
                $trackPoint = TrackPoint::with(
                    $point['lat'],
                    $point['lng'],
                    new \DateTimeImmutable($point['time']),
                    $elevation,
                    $extensions
                );

                $trackPoints[] = $trackPoint;
            }
        }

        $sport = SportMapperInterface::OTHER;
        if (isset($json['sport'])) {
            $sport = $this->sportMapper()->sportFromCode((string)$json['sport']);
        }
        $track = new Track($trackPoints, $sport);

        return new Workout([$track]);
    }

    /**
     * {@inheritdoc}
     */
    public function workouts(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $list = $this->list($startDate, $endDate);
        $workouts = array();
        foreach ($list as $workoutSummary) {
            $workouts[] = $this->workout($workoutSummary->workoutId());
        }
        return $workouts;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Workout $workout): bool
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
    public function sportMapper(): SportMapperInterface
    {
        return new SportMapper();
    }

    /**
     * {@inheritdoc}
     */
    public static function ID(): string
    {
        return 'endomondo';
    }
}
