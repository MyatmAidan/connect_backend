<?php

namespace App\Providers;

use App\Repositories\Contracts\AdminLogRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ConnectionRepositoryInterface;
use App\Repositories\Contracts\ConnectionRequestRepositoryInterface;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\DeveloperProfileRepositoryInterface;
use App\Repositories\Contracts\EventRegistrationRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\EventRequestRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\SkillRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\AdminLogRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ConnectionRepository;
use App\Repositories\Eloquent\ConnectionRequestRepository;
use App\Repositories\Eloquent\ConversationRepository;
use App\Repositories\Eloquent\DeveloperProfileRepository;
use App\Repositories\Eloquent\EventRegistrationRepository;
use App\Repositories\Eloquent\EventRepository;
use App\Repositories\Eloquent\EventRequestRepository;
use App\Repositories\Eloquent\MessageRepository;
use App\Repositories\Eloquent\NotificationLogRepository;
use App\Repositories\Eloquent\ReportRepository;
use App\Repositories\Eloquent\SkillRepository;
use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $bindings = [
            UserRepositoryInterface::class => UserRepository::class,
            DeveloperProfileRepositoryInterface::class => DeveloperProfileRepository::class,
            SkillRepositoryInterface::class => SkillRepository::class,
            CategoryRepositoryInterface::class => CategoryRepository::class,
            ConnectionRequestRepositoryInterface::class => ConnectionRequestRepository::class,
            ConnectionRepositoryInterface::class => ConnectionRepository::class,
            ConversationRepositoryInterface::class => ConversationRepository::class,
            MessageRepositoryInterface::class => MessageRepository::class,
            EventRepositoryInterface::class => EventRepository::class,
            EventRequestRepositoryInterface::class => EventRequestRepository::class,
            EventRegistrationRepositoryInterface::class => EventRegistrationRepository::class,
            ReportRepositoryInterface::class => ReportRepository::class,
            NotificationLogRepositoryInterface::class => NotificationLogRepository::class,
            AdminLogRepositoryInterface::class => AdminLogRepository::class,
        ];

        foreach ($bindings as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
