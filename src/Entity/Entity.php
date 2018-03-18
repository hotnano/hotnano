<?php

namespace HotNano\Entity;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class Entity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $image;

    /**
     * Owner Address
     *
     * @var string|null
     */
    private $ownerAddress;

    /**
     * @var Carbon|null
     */
    private $ownedSince;

    /**
     * Amount of Nanos payed by the current Owner.
     *
     * @var int|null
     */
    private $currentPrice;

    /**
     * Amount of Nanos to pay to become the new Owner.
     *
     * @var int|null
     */
    private $targetPrice;

    /**
     * Address to claim the entity.
     *
     * @var string|null
     */
    private $targetAddress;

    /**
     * The next time when to become a new Owner.
     * All Nanos which will be sent during this time will be refunded.
     *
     * @var Carbon|null
     */
    private $targetTime;

    /**
     * Previous processed block of the history.
     *
     * @var string|null
     * @deprecated
     */
    private $frontier;

    /**
     * @var int
     */
    private $historyOffset;

    /**
     * @var string|null
     */
    private $errorMessage;

    /**
     * @var Carbon|null
     */
    private $updatedAt;

    public function __construct()
    {
        $uuid = Uuid::uuid4();
        $this->id = $uuid->toString();
        $this->isActive = false;
        $this->historyOffset = 0;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;

        $this->update();
    }

    /**
     * @return null|string
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param null|string $image
     */
    public function setImage(?string $image)
    {
        $this->image = $image;

        $this->update();
    }

    /**
     * @return null|string
     */
    public function getOwnerAddress(): ?string
    {
        return $this->ownerAddress;
    }

    /**
     * @param null|string $ownerAddress
     */
    public function setOwnerAddress(?string $ownerAddress)
    {
        $this->ownerAddress = $ownerAddress;

        $this->update();
    }

    /**
     * @return Carbon|null
     */
    public function getOwnedSince(): ?Carbon
    {
        return $this->ownedSince;
    }

    /**
     * @param Carbon|null $ownedSince
     */
    public function setOwnedSince(?Carbon $ownedSince)
    {
        $this->ownedSince = $ownedSince;

        $this->update();
    }

    /**
     * @return int|null
     */
    public function getCurrentPrice(): ?int
    {
        return $this->currentPrice;
    }

    /**
     * @param int|null $currentPrice
     */
    public function setCurrentPrice(?int $currentPrice): void
    {
        $this->currentPrice = $currentPrice;

        $this->update();
    }

    /**
     * @return int|null
     */
    public function getTargetPrice(): ?int
    {
        return $this->targetPrice;
    }

    /**
     * @param int|null $targetPrice
     */
    public function setTargetPrice(?int $targetPrice): void
    {
        $this->targetPrice = $targetPrice;

        $this->update();
    }

    /**
     * @return null|string
     */
    public function getTargetAddress(): ?string
    {
        return $this->targetAddress;
    }

    /**
     * @param null|string $targetAddress
     */
    public function setTargetAddress(?string $targetAddress): void
    {
        $this->targetAddress = $targetAddress;

        $this->update();
    }

    /**
     * @return Carbon|null
     */
    public function getTargetTime(): ?Carbon
    {
        return $this->targetTime;
    }

    public function getTargetTimeFormatted()
    {
        if (null === $this->targetTime) {
            return 'N/A';
        }

        return $this->targetTime->format('Y-m-d H:i:s');
    }

    /**
     * @param Carbon|null $targetTime
     */
    public function setTargetTime(?Carbon $targetTime): void
    {
        $this->targetTime = $targetTime;

        $this->update();
    }

    /**
     * @return null|string
     * @deprecated
     */
    public function getFrontier(): ?string
    {
        return $this->frontier;
    }

    /**
     * @param null|string $frontier
     * @deprecated
     */
    public function setFrontier(?string $frontier): void
    {
        $this->frontier = $frontier;
    }

    /**
     * @return int
     */
    public function getHistoryOffset(): int
    {
        return $this->historyOffset;
    }

    /**
     * @param int $historyOffset
     */
    public function setHistoryOffset(int $historyOffset): void
    {
        $this->historyOffset = $historyOffset;
    }

    public function canBeClaimed(): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        if (null === $this->targetAddress) {
            return false;
        }
        if (null !== $this->targetTime && Carbon::now('UTC') < $this->targetTime) {
            return false;
        }
        if (null === $this->targetPrice) {
            return false;
        }

        return true;
    }

    /**
     * @return null|string
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @param null|string $errorMessage
     */
    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;

        $this->update();
    }

    /**
     * @return Carbon|null
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @param Carbon|null $updatedAt
     */
    public function setUpdatedAt(?Carbon $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    private function update()
    {
        $this->setUpdatedAt(Carbon::now('UTC'));
    }

    public function toArray(): array
    {
        if ($this->getOwnedSince()) {
            $ownerSince = $this->getOwnedSince()->format('c');
        } else {
            $ownerSince = null;
        }

        if ($this->getUpdatedAt()) {
            $updatedAt = $this->getUpdatedAt()->format('c');
        } else {
            $updatedAt = null;
        }

        if ($this->getTargetTime()) {
            $targetTime = $this->getTargetTime()->format('c');
        } else {
            $targetTime = null;
        }

        return [
            'id' => $this->getId(),
            'is_active' => $this->isActive(),
            'name' => $this->getName(),
            'image' => $this->getImage(),

            'owner_address' => $this->getOwnerAddress(),
            'owner_since' => $ownerSince,

            'current_price' => $this->getCurrentPrice(),
            'target_price' => $this->getTargetPrice(),
            'target_address' => $this->getTargetAddress(),
            'target_time' => $targetTime,

            // 'frontier' => $this->getFrontier(),
            'history_offset' => $this->getHistoryOffset(),

            'error_message' => $this->getErrorMessage(),
            'updated_at' => $updatedAt,
        ];
    }

    public static function loadFromArray(array $a): Entity
    {
        $e = new Entity();

        $mappings = [
            [
                'name' => 'id',
                'setter' => 'setId',
            ],
            [
                'name' => 'is_active',
                'setter' => 'setIsActive',
            ],
            [
                'name' => 'name',
                'setter' => 'setName',
            ],
            [
                'name' => 'image',
                'setter' => 'setImage',
            ],

            [
                'name' => 'owner_address',
                'setter' => 'setOwnerAddress',
            ],
            [
                'name' => 'owner_since',
                'converter_fn' => self::getTimeParseFn(),
                'setter' => 'setOwnerSince',
            ],

            [
                'name' => 'current_price',
                'converter_fn' => function ($val) {
                    return floatval($val);
                },
                'setter' => 'setCurrentPrice',
            ],
            [
                'name' => 'target_price',
                'converter_fn' => function ($val) {
                    return floatval($val);
                },
                'setter' => 'setTargetPrice',
            ],
            [
                'name' => 'target_address',
                'setter' => 'setTargetAddress',
            ],
            [
                'name' => 'target_time',
                'converter_fn' => self::getTimeParseFn(),
                'setter' => 'setTargetTime',
            ],
            // [
            //     'name' => 'frontier',
            //     'setter' => 'setFrontier',
            // ],
            [
                'name' => 'history_offset',
                'setter' => 'setHistoryOffset',
            ],

            [
                'name' => 'error_message',
                'setter' => 'setErrorMessage',
            ],
            [
                'name' => 'updated_at',
                'converter_fn' => self::getTimeParseFn(),
                'setter' => 'setUpdatedAt',
            ],
        ];

        $reflectionClass = new \ReflectionClass($e);

        foreach ($mappings as $mapping) {
            if (!array_key_exists($mapping['name'], $a) || !$a[$mapping['name']]) {
                continue;
            }

            if (array_key_exists('converter_fn', $mapping)) {
                $converterFn = $mapping['converter_fn'];
                $val = $converterFn($a[$mapping['name']]);
            } else {
                $val = $a[$mapping['name']];
            }

            $reflectionMethod = $reflectionClass->getMethod($mapping['setter']);
            $reflectionMethod->invoke($e, $val);
        }

        return $e;
    }

    public static function getTimeParseFn()
    {
        $fn = function ($val) {
            return Carbon::parse($val);
        };
        return $fn;
    }
}
