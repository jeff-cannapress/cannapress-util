<?php

declare(strict_types=1);

namespace CannaPress\Util;

abstract class CronJob
{
    protected function __construct(private string $name)
    {
        add_action($this->hook_name(), [$this, 'execute']);
    }
    protected function hook_name()
    {
        return (CronJob::class) . '\\' . ($this->name);
    }
    public function ensure_scheduled(array $args = [])
    {
        if (wp_next_scheduled($this->hook_name()) === false) {
            $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->add(new \DateInterval('PT12H'))->getTimestamp();
            $timestamp = apply_filters('cannapress_cron_job_schedule_initial_timestamp', $timestamp, $this->name);
            $recurrence = apply_filters('cannapress_cron_job_schedule_recurrence', 'twicedaily', $this->name);
            wp_schedule_event($timestamp, $recurrence, $this->hook_name(), $args);
        }
    }
    abstract public function execute(...$args);

    public function clear_scheduled()
    {
        if (($next = wp_next_scheduled($this->hook_name())) !== false) {
            wp_unschedule_event($next, $this->hook_name());
        }
    }
}
