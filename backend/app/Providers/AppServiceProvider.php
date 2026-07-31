<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Product;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Policies\ModulePolicy;
use App\Policies\ProductPolicy;
use App\Support\DatabaseCommandGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DatabaseCommandGuard::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $guard = $this->app->make(DatabaseCommandGuard::class);

        DB::prohibitDestructiveCommands(! $guard->isIsolatedTestDatabase());
        SeedCommand::prohibit(! $guard->isIsolatedTestDatabase());

        Event::listen(CommandStarting::class, function (CommandStarting $event) use ($guard): void {
            $guard->assertCommandIsSafe($event->command);
        });

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
    }
}
