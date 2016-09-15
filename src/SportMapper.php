<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo;

use SportTrackerConnector\Core\Workout\AbstractSportMapper;

/**
 * Sport mapper for Endomondo tracker.
 */
class SportMapper extends AbstractSportMapper
{
    const SPORT_RUNNING = 0;
    const SPORT_RUNNING_TREADMILL = 88;
    const SPORT_CYCLING_TRANSPORT = 1;
    const SPORT_CYCLING_SPORT = 2;
    const SPORT_MOUNTAIN_BIKING = 3;
    const SPORT_SKATING = 4;
    const SPORT_ROLLER_SKIING = 5;
    const SPORT_SKIING_CROSS_COUNTRY = 6;
    const SPORT_SKIING_DOWNHILL = 7;
    const SPORT_SNOWBOARDING = 8;
    const SPORT_KAYAKING = 9;
    const SPORT_KITE_SURFING = 10;
    const SPORT_ROWING = 11;
    const SPORT_SAILING = 12;
    const SPORT_WINDSURFING = 13;
    const SPORT_FITNESS_WALKING = 14;
    const SPORT_GOLF = 15;
    const SPORT_HIKING = 16;
    const SPORT_ORIENTEERING = 17;
    const SPORT_WALKING = 18;
    const SPORT_RIDING = 19;
    const SPORT_SWIMMING = 20;
    const SPORT_CYCLING_INDOOR = 21;
    const SPORT_OTHER = 22;
    const SPORT_AEROBICS = 23;
    const SPORT_BADMINTON = 24;
    const SPORT_BASEBALL = 25;
    const SPORT_BASKETBALL = 26;
    const SPORT_BOXING = 27;
    const SPORT_CLIMBING_STAIRS = 28;
    const SPORT_CRICKET = 29;
    const SPORT_CROSS_TRAINING = 30;
    const SPORT_DANCING = 31;
    const SPORT_FENCING = 32;
    const SPORT_FOOTBALL_AMERICAN = 33;
    const SPORT_FOOTBALL_RUGBY = 34;
    const SPORT_FOOTBALL_SOCCER = 35;
    const SPORT_HANDBALL = 36;
    const SPORT_HOCKEY = 37;
    const SPORT_PILATES = 38;
    const SPORT_POLO = 39;
    const SPORT_SCUBA_DIVING = 40;
    const SPORT_SQUASH = 41;
    const SPORT_TABLE_TENIS = 42;
    const SPORT_TENNIS = 43;
    const SPORT_VOLLEYBALL_BEACH = 44;
    const SPORT_VOLLEYBALL_INDOOR = 45;
    const SPORT_WEIGHT_TRAINING = 46;
    const SPORT_YOGA = 47;
    const SPORT_MARTIAL_ARTS = 48;
    const SPORT_GYMNASTICS = 49;
    const SPORT_STEP_COUNTER = 50;
    const SPORT_CIRCUIT_TRAINING = 87;
    const SPORT_CLIMBING = 93;
    const SPORT_ICE_SKATING = 100;

    /**
     * {@inheritdoc}
     */
    public function getMap()
    {
        return array(
            self::RUNNING => self::SPORT_RUNNING,
            self::RUNNING_TREADMILL => self::SPORT_RUNNING_TREADMILL,
            self::WALKING => self::SPORT_WALKING,
            self::WALKING_FITNESS => self::SPORT_FITNESS_WALKING,
            self::CYCLING_SPORT => self::SPORT_CYCLING_SPORT,
            self::CYCLING_TRANSPORT => self::SPORT_CYCLING_TRANSPORT,
            self::CYCLING_INDOOR => self::SPORT_CYCLING_INDOOR,
            self::CYCLING_MOUNTAIN => self::SPORT_MOUNTAIN_BIKING,
            self::SWIMMING => self::SPORT_SWIMMING,
            self::GOLF => self::SPORT_GOLF,
            self::KAYAKING => self::SPORT_KAYAKING,
            self::KITE_SURFING => self::SPORT_KITE_SURFING,
            self::HIKING => self::SPORT_HIKING,
            self::SKATING => self::SPORT_SKATING,
            self::WEIGHT_TRAINING => self::SPORT_WEIGHT_TRAINING,
            self::OTHER => self::SPORT_OTHER,
        );
    }
}
