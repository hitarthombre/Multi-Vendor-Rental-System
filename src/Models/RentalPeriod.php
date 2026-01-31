<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;
use DateTime;
use DateInterval;

/**
 * RentalPeriod Model
 * 
 * Represents a time period for rentals with start and end dates
 */
class RentalPeriod
{
    private string $id;
    private DateTime $startDateTime;
    private DateTime $endDateTime;
    private int $durationValue;
    private string $durationUnit;

    public const UNIT_HOURLY = 'Hourly';
    public const UNIT_DAILY = 'Daily';
    public const UNIT_WEEKLY = 'Weekly';
    public const UNIT_MONTHLY = 'Monthly';

    public const VALID_UNITS = [
        self::UNIT_HOURLY,
        self::UNIT_DAILY,
        self::UNIT_WEEKLY,
        self::UNIT_MONTHLY
    ];

    /**
     * Constructor
     */
    public function __construct(
        string $id,
        DateTime $startDateTime,
        DateTime $endDateTime,
        int $durationValue,
        string $durationUnit
    ) {
        $this->id = $id;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->durationValue = $durationValue;
        $this->durationUnit = $durationUnit;
    }

    /**
     * Create a new rental period with generated ID
     */
    public static function create(
        DateTime $startDateTime,
        DateTime $endDateTime,
        string $durationUnit = self::UNIT_DAILY
    ): self {
        $id = UUID::generate();
        
        // Calculate duration value based on unit
        $durationValue = self::calculateDurationValue($startDateTime, $endDateTime, $durationUnit);
        
        return new self($id, $startDateTime, $endDateTime, $durationValue, $durationUnit);
    }

    /**
     * Create from date strings
     */
    public static function createFromStrings(
        string $startDateTime,
        string $endDateTime,
        string $durationUnit = self::UNIT_DAILY
    ): self {
        $start = new DateTime($startDateTime);
        $end = new DateTime($endDateTime);
        
        return self::create($start, $end, $durationUnit);
    }

    /**
     * Calculate duration value based on unit
     */
    private static function calculateDurationValue(DateTime $start, DateTime $end, string $unit): int
    {
        $interval = $start->diff($end);
        
        switch ($unit) {
            case self::UNIT_HOURLY:
                return ($interval->days * 24) + $interval->h + ($interval->i > 0 ? 1 : 0);
            
            case self::UNIT_DAILY:
                return max(1, $interval->days + ($interval->h > 0 || $interval->i > 0 ? 1 : 0));
            
            case self::UNIT_WEEKLY:
                return max(1, ceil($interval->days / 7));
            
            case self::UNIT_MONTHLY:
                return max(1, ($interval->y * 12) + $interval->m + ($interval->d > 0 ? 1 : 0));
            
            default:
                return 1;
        }
    }

    /**
     * Validate that end date is after start date
     */
    public function isValid(): bool
    {
        return $this->endDateTime > $this->startDateTime;
    }

    /**
     * Check if this period overlaps with another period
     */
    public function overlapsWith(RentalPeriod $other): bool
    {
        return $this->startDateTime < $other->endDateTime && 
               $this->endDateTime > $other->startDateTime;
    }

    /**
     * Get total duration in hours
     */
    public function getTotalHours(): int
    {
        $interval = $this->startDateTime->diff($this->endDateTime);
        return ($interval->days * 24) + $interval->h + ($interval->i > 0 ? 1 : 0);
    }

    /**
     * Get total duration in days
     */
    public function getTotalDays(): int
    {
        $interval = $this->startDateTime->diff($this->endDateTime);
        return max(1, $interval->days + ($interval->h > 0 || $interval->i > 0 ? 1 : 0));
    }

    // Getters
    public function getId(): string
    {
        return $this->id;
    }

    public function getStartDateTime(): DateTime
    {
        return $this->startDateTime;
    }

    public function getEndDateTime(): DateTime
    {
        return $this->endDateTime;
    }

    public function getDurationValue(): int
    {
        return $this->durationValue;
    }

    public function getDurationUnit(): string
    {
        return $this->durationUnit;
    }

    public function getStartDateTimeString(): string
    {
        return $this->startDateTime->format('Y-m-d H:i:s');
    }

    public function getEndDateTimeString(): string
    {
        return $this->endDateTime->format('Y-m-d H:i:s');
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'start_datetime' => $this->getStartDateTimeString(),
            'end_datetime' => $this->getEndDateTimeString(),
            'duration_value' => $this->durationValue,
            'duration_unit' => $this->durationUnit
        ];
    }
}