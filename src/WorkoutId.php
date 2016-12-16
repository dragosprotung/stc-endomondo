<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Endomondo;

use SportTrackerConnector\Core\Workout\WorkoutIdInterface;

class WorkoutId implements WorkoutIdInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->id;
    }

    /**
     * @param WorkoutIdInterface $other
     * @return bool
     */
    public function equals(WorkoutIdInterface $other): bool
    {
        return $this->toString() === $other->toString();
    }
}
