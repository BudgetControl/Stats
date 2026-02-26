<?php
declare(strict_types=1);

namespace Budgetcontrol\Stats\Domain\Entity;

use Carbon\Carbon;
use JsonSerializable;

class ElasticTransaction implements JsonSerializable
{
    private ?string $uuid = null;
    private ?string $note = null;
    private ?int $workspace_id = null;
    private ?float $amount = null;
    private ?string $type = null;
    private ?string $payment_type = null;
    private ?string $currency = null;
    private ?int $category_id = null;
    private ?string $category_name = null;
    private ?int $wallet_id = null;
    private ?array $wallet = null;
    private ?string $date = null;
    private ?int $timestamp = null;
    private ?int $year = null;
    private ?int $month = null;
    private ?int $day = null;
    private ?int $day_of_week = null;
    private ?int $week_of_year = null;
    private ?int $quarter = null;
    private ?array $tags = null;
    private ?bool $have_payee = null;
    private ?array $payee = null;
    private ?bool $confirmed = null;
    private ?bool $planned = null;
    private ?bool $have_warranty = null;
    private ?bool $is_transfer = null;
    private ?array $transfer_relation = null;
    private mixed $geolocalization = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    public function hydrate(array $data): self
    {
        foreach ($data as $key => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
        return $this;
    }

    // Getters
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getWorkspaceId(): ?int
    {
        return $this->workspace_id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getPaymentType(): ?string
    {
        return $this->payment_type;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getCategoryId(): ?int
    {
        return $this->category_id;
    }

    public function getCategoryName(): ?string
    {
        return $this->category_name;
    }

    public function getWalletId(): ?int
    {
        return $this->wallet_id;
    }

    public function getWallet(): ?array
    {
        return $this->wallet;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->day_of_week;
    }

    public function getWeekOfYear(): ?int
    {
        return $this->week_of_year;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getHavePayee(): ?bool
    {
        return $this->have_payee;
    }

    public function getPayee(): ?array
    {
        return $this->payee;
    }

    public function getConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function getPlanned(): ?bool
    {
        return $this->planned;
    }

    public function getHaveWarranty(): ?bool
    {
        return $this->have_warranty;
    }

    public function getIsTransfer(): ?bool
    {
        return $this->is_transfer;
    }

    public function getTransferRelation(): ?array
    {
        return $this->transfer_relation;
    }

    public function getGeolocalization()
    {
        return $this->geolocalization;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    // Setters
    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;
        return $this;
    }

    public function setWorkspaceId(?int $workspace_id): self
    {
        $this->workspace_id = $workspace_id;
        return $this;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setPaymentType(?string $payment_type): self
    {
        $this->payment_type = $payment_type;
        return $this;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setCategoryId(?int $category_id): self
    {
        $this->category_id = $category_id;
        return $this;
    }

    public function setCategoryName(?string $category_name): self
    {
        $this->category_name = $category_name;
        return $this;
    }

    public function setWalletId(?int $wallet_id): self
    {
        $this->wallet_id = $wallet_id;
        return $this;
    }

    public function setWallet(?array $wallet): self
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function setDate($date): self
    {
        $this->date = $date;
        $carbon = Carbon::createFromFormat('Y-m-d H:i:s', $date);
        $this->setTimestamp($carbon->getTimestamp());
        $this->setYear((int) $carbon->format('Y'));
        $this->setMonth((int) $carbon->format('m'));
        $this->setDay((int) $carbon->format('d'));
        $this->setDayOfWeek((int) $carbon->format('N'));
        $this->setWeekOfYear((int) $carbon->format('W'));
        $this->setQuarter($carbon->quarter);
        
        return $this;
    }

    public function setTimestamp(?int $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function setYear(?int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function setMonth(?int $month): self
    {
        $this->month = $month;
        return $this;
    }

    public function setDay(?int $day): self
    {
        $this->day = $day;
        return $this;
    }

    public function setDayOfWeek(?int $day_of_week): self
    {
        $this->day_of_week = $day_of_week;
        return $this;
    }

    public function setWeekOfYear(?int $week_of_year): self
    {
        $this->week_of_year = $week_of_year;
        return $this;
    }

    public function setQuarter(?int $quarter): self
    {
        $this->quarter = $quarter;
        return $this;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function setHavePayee(?bool $have_payee): self
    {
        $this->have_payee = $have_payee;
        return $this;
    }

    public function setPayee(?array $payee): self
    {
        $this->payee = $payee;
        return $this;
    }

    public function setConfirmed(?bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function setPlanned(?bool $planned): self
    {
        $this->planned = $planned;
        return $this;
    }

    public function setHaveWarranty(?bool $have_warranty): self
    {
        $this->have_warranty = $have_warranty;
        return $this;
    }

    public function setIsTransfer(?bool $is_transfer): self
    {
        $this->is_transfer = $is_transfer;
        return $this;
    }

    public function setTransferRelation(?array $transfer_relation): self
    {
        $this->transfer_relation = $transfer_relation;
        return $this;
    }

    public function setGeolocalization($geolocalization): self
    {
        $this->geolocalization = $geolocalization;
        return $this;
    }

    public function setCreatedAt($created_at): self
    {
        if ($created_at instanceof Carbon) {
            $this->created_at = $created_at->toISOString();
        } elseif (is_string($created_at)) {
            $this->created_at = $created_at;
        }
        return $this;
    }

    public function setUpdatedAt($updated_at): self
    {
        if ($updated_at instanceof Carbon) {
            $this->updated_at = $updated_at->toISOString();
        } elseif (is_string($updated_at)) {
            $this->updated_at = $updated_at;
        }
        return $this;
    }

    // Utility methods
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'note' => $this->note,
            'workspace_id' => $this->workspace_id,
            'amount' => $this->amount,
            'type' => $this->type,
            'payment_type' => $this->payment_type,
            'currency' => $this->currency,
            'category_id' => $this->category_id,
            'category_name' => $this->category_name,
            'wallet_id' => $this->wallet_id,
            'wallet' => $this->wallet,
            'date' => $this->date,
            'timestamp' => $this->timestamp,
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'day_of_week' => $this->day_of_week,
            'week_of_year' => $this->week_of_year,
            'quarter' => $this->quarter,
            'tags' => $this->tags,
            'have_payee' => $this->have_payee,
            'payee' => $this->payee,
            'confirmed' => $this->confirmed,
            'planned' => $this->planned,
            'have_warranty' => $this->have_warranty,
            'is_transfer' => $this->is_transfer,
            'transfer_relation' => $this->transfer_relation,
            'geolocalization' => $this->geolocalization,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromElasticsearchResult(array $hit): self
    {
        $source = $hit['_source'] ?? [];
        $source['uuid'] = $hit['_id'] ?? $source['uuid'] ?? null;
        return new self($source);
    }

    /**
     * Get Elasticsearch document ID (uses uuid)
     */
    public function getDocumentId(): ?string
    {
        return $this->uuid;
    }

    /**
     * Get only non-null fields for partial updates
     */
    public function getUpdatedFields(): array
    {
        return array_filter($this->toArray(), fn($value) => $value !== null);
    }

    /**
     * Check if transaction is valid for indexing
     */
    public function isValid(): bool
    {
        return !empty($this->uuid) && $this->amount !== null && !empty($this->date);
    }

    /**
     * Update date fields based on a Carbon instance
     */
    public function updateDateFields(Carbon $carbon): self
    {
        $this->date = $carbon->format('Y-m-d H:i:s');
        $this->timestamp = $carbon->getTimestamp();
        $this->year = (int) $carbon->format('Y');
        $this->month = (int) $carbon->format('m');
        $this->day = (int) $carbon->format('d');
        $this->day_of_week = (int) $carbon->format('N');
        $this->week_of_year = (int) $carbon->format('W');
        $this->quarter = $carbon->quarter;
        return $this;
    }

    public static function mapping(): array
    {
        return [
            'properties' => [
                'uuid' => ['type' => 'keyword'],
                'note' => [
                    'type' => 'text',
                    'analyzer' => 'my_analyzer',
                    'fields' => [
                        'keyword' => [
                            'type' => 'keyword',
                        ],
                    ],
                ],
                'workspace_id' => ['type' => 'integer'],
                'amount' => ['type' => 'float'],
                'type' => ['type' => 'keyword'],
                'payment_type' => ['type' => 'keyword'],
                'currency' => ['type' => 'keyword'],
                'category_id' => ['type' => 'integer'],
                'category_name' => ['type' => 'keyword'],
                'wallet_id' => ['type' => 'integer'],
                'wallet' => [
                    'type' => 'object',
                    'enabled' => true
                ],
                'date' => ['type' => 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
                'timestamp' => ['type' => 'long'],
                'year' => ['type' => 'integer'],
                'month' => ['type' => 'integer'],
                'day' => ['type' => 'integer'],
                'day_of_week' => ['type' => 'integer'],
                'week_of_year' => ['type' => 'integer'],
                'quarter' => ['type' => 'integer'],
                'tags' => [
                    'type' => 'object',
                    'enabled' => true
                ],
                'have_payee' => ['type' => 'boolean'],
                'payee' => [
                    'type' => 'object',
                    'enabled' => true
                ],
                'confirmed' => ['type' => 'boolean'],
                'planned' => ['type' => 'boolean'],
                'have_warranty' => ['type' => 'boolean'],
                'is_transfer' => ['type' => 'boolean'],
                'transfer_relation' => [
                    'type' => 'object',
                    'properties' => [
                        'transfer_from' => ['type' => 'keyword'],
                        'transfer_to' => ['type' => 'keyword']
                    ]
                ],
                'geolocalization' => ['type' => 'geo_point'],
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],
            ],
        ];
    }
}
