<?php

namespace Modules\Calendar\database\factories;

use App\Enums\EventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\app\Models\Event;
use Modules\FieldManager\app\Models\Client;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+2 months');
        $end = $this->faker->dateTimeBetween($start, $start->format('Y-m-d H:i:s').' +4 hours');

        // Get a safe list of event types
        $eventTypes = [
            EventType::MEETING,
            EventType::TRAINING,
            EventType::COMPANY_EVENT,
            EventType::DEADLINE,
            EventType::OTHER,
        ];

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'start' => $start,
            'end' => $end,
            'all_day' => $this->faker->boolean(20), // 20% chance of all-day
            'color' => $this->faker->hexColor(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'location' => $this->faker->optional()->address(),
            'meeting_link' => $this->faker->optional()->url(),
            'related_type' => function () {
                // Randomly assign related entity types for realistic data
                $availableTypes = [];
                if (class_exists('Modules\FieldManager\app\Models\Client')) {
                    $availableTypes[] = 'Modules\FieldManager\app\Models\Client';
                }
                if (class_exists('Modules\CRMCore\app\Models\Company')) {
                    $availableTypes[] = 'Modules\CRMCore\app\Models\Company';
                }
                if (class_exists('Modules\CRMCore\app\Models\Contact')) {
                    $availableTypes[] = 'Modules\CRMCore\app\Models\Contact';
                }

                // 60% chance of having a related entity
                return $this->faker->boolean(60) && ! empty($availableTypes)
                  ? $this->faker->randomElement($availableTypes)
                  : null;
            },
            'related_id' => function (array $attributes) {
                if (! $attributes['related_type']) {
                    return null;
                }

                try {
                    // Use proper class instantiation instead of app() binding
                    if ($attributes['related_type'] === 'Modules\FieldManager\app\Models\Client' && class_exists('Modules\FieldManager\app\Models\Client')) {
                        return \Modules\FieldManager\app\Models\Client::inRandomOrder()->first()?->id ?? null;
                    }
                    if ($attributes['related_type'] === 'Modules\CRMCore\app\Models\Company' && class_exists('Modules\CRMCore\app\Models\Company')) {
                        return \Modules\CRMCore\app\Models\Company::inRandomOrder()->first()?->id ?? null;
                    }
                    if ($attributes['related_type'] === 'Modules\CRMCore\app\Models\Contact' && class_exists('Modules\CRMCore\app\Models\Contact')) {
                        return \Modules\CRMCore\app\Models\Contact::inRandomOrder()->first()?->id ?? null;
                    }

                    return null;
                } catch (\Exception $e) {
                    return null;
                }
            },
            'created_by_id' => function () {
                return User::inRandomOrder()->first()?->id ?? User::factory()->create()->id;
            },
            'updated_by_id' => function () {
                return User::inRandomOrder()->first()?->id ?? User::factory()->create()->id;
            },
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'start' => $this->faker->dateTimeBetween('-1 month', '+2 months')->format('Y-m-d'),
            'end' => null,
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::MEETING,
            'meeting_link' => $this->faker->url(),
        ]);
    }

    public function clientAppointment(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::CLIENT_APPOINTMENT,
            'related_type' => Client::class,
            'related_id' => function () {
                // Only create client if Client model exists
                if (class_exists(Client::class)) {
                    return Client::inRandomOrder()->first()?->id ?? Client::factory()->create()->id;
                }

                return null;
            },
        ]);
    }

    public function companyEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::COMPANY_EVENT,
            'related_type' => 'Modules\CRMCore\app\Models\Company',
            'related_id' => function () {
                if (class_exists('Modules\CRMCore\app\Models\Company')) {
                    return \Modules\CRMCore\app\Models\Company::inRandomOrder()->first()?->id ?? 1;
                }

                return null;
            },
        ]);
    }

    public function contactMeeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::MEETING,
            'related_type' => 'Modules\CRMCore\app\Models\Contact',
            'related_id' => function () {
                if (class_exists('Modules\CRMCore\app\Models\Contact')) {
                    return \Modules\CRMCore\app\Models\Contact::inRandomOrder()->first()?->id ?? 1;
                }

                return null;
            },
        ]);
    }

    public function projectMeeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::PROJECT_MEETING,
            'meeting_link' => $this->faker->url(),
        ]);
    }
}
