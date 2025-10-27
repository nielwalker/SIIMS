<?php

namespace App\Providers;

use App\Repositories\ApplicationRepository;
use App\Repositories\ApplicationRepositoryInterface;
use App\Repositories\CollegeRepository;
use App\Repositories\CollegeRepositoryInterface;
use App\Repositories\CompanyRepository;
use App\Repositories\CompanyRepositoryInterface;
use App\Repositories\CoordinatorRepository;
use App\Repositories\CoordinatorRepositoryInterface;
use App\Repositories\DailyRecordRepository;
use App\Repositories\DailyRecordRepositoryInterface;
use App\Repositories\DashboardRepository;
use App\Repositories\DashboardRepositoryInterface;
use App\Repositories\DeanRepository;
use App\Repositories\DeanRepositoryInterface;
use App\Repositories\DocumentSubmissionRepository;
use App\Repositories\DocumentSubmissionRepositoryInterface;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\DocumentTypeRepositoryInterface;
use App\Repositories\EndorsementLetterRequestRepository;
use App\Repositories\EndorsementLetterRequestRepositoryInterface;
use App\Repositories\EndorseStudentRepository;
use App\Repositories\EndorseStudentRepositoryInterface;
use App\Repositories\LogRepository;
use App\Repositories\LogRepositoryInterface;
use App\Repositories\OfficeRepository;
use App\Repositories\OfficeRepositoryInterface;
use App\Repositories\ProfileRepository;
use App\Repositories\ProfileRepositoryInterface;
use App\Repositories\ProgramRepository;
use App\Repositories\ProgramRepositoryInterface;
use App\Repositories\SectionRepository;
use App\Repositories\SectionRepositoryInterface;
use App\Repositories\StudentRepository;
use App\Repositories\StudentRepositoryInterface;
use App\Repositories\SupervisorRepository;
use App\Repositories\SupervisorRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\V3\CollegeV3Repository;
use App\Repositories\V3\CollegeV3RepositoryInterface;
use App\Repositories\V3\StudentV3Repository;
use App\Repositories\V3\StudentV3RepositoryInterface;
use App\Repositories\WeeklyRecordRepository;
use App\Repositories\WeeklyRecordRepositoryInterface;
use App\Repositories\WorkPostRepository;
use App\Repositories\WorkPostRepositoryInterface;
use App\Services\ActionLogger;
use App\Services\ApplicationService;
use App\Services\DailyRecordService;
use App\Services\DocumentTypeService;
use App\Services\EndorsementLetterRequestService;
use App\Services\HelperService;
use App\Services\SectionService;
use App\Services\V3\CollegeV3Service;
use App\Services\V3\StudentV3Service;
use App\Services\WeeklyRecordService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the ActionLogger service as a singleton
        $this->app->singleton(ActionLogger::class, function ($app) {
            return new ActionLogger();
        });
        
         // BIND SERVICES (SINGLETON)
        $this->app->singleton(HelperService::class, function ($app) {
            return new HelperService();
        });

        // BIND SERVICES (V3)
        $this->app->bind(StudentV3Service::class, StudentV3Service::class);
        $this->app->bind(CollegeV3Service::class, CollegeV3Service::class);

        // BIND SERVICES (V1)
        $this->app->bind(DailyRecordService::class, DailyRecordService::class);
        $this->app->bind(DocumentTypeService::class, DocumentTypeService::class);
        $this->app->bind(ApplicationService::class, concrete: ApplicationService::class);
        $this->app->bind(EndorsementLetterRequestService::class, concrete: EndorsementLetterRequestService::class);
        $this->app->bind(SectionService::class, concrete: SectionService::class);
        $this->app->bind(WeeklyRecordService::class, concrete: WeeklyRecordService::class);
    
        // BIND REPOSITORIES (V3)
        $this->app->bind(StudentV3RepositoryInterface::class, StudentV3Repository::class);
        $this->app->bind(CollegeV3RepositoryInterface::class, CollegeV3Repository::class);

        // BIND REPOSITORIES
        $this->app->bind(DailyRecordRepositoryInterface::class, DailyRecordRepository::class);
        $this->app->bind(DashboardRepositoryInterface::class, DashboardRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);
        $this->app->bind(DocumentTypeRepositoryInterface::class, DocumentTypeRepository::class);
        $this->app->bind(SectionRepositoryInterface::class, SectionRepository::class);
        $this->app->bind(CollegeRepositoryInterface::class, CollegeRepository::class);
        $this->app->bind(ProgramRepositoryInterface::class, ProgramRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(DeanRepositoryInterface::class, DeanRepository::class);
        $this->app->bind(CoordinatorRepositoryInterface::class, CoordinatorRepository::class);
        $this->app->bind(SupervisorRepositoryInterface::class, SupervisorRepository::class);
        $this->app->bind(WorkPostRepositoryInterface::class, WorkPostRepository::class);
        $this->app->bind(ApplicationRepositoryInterface::class, ApplicationRepository::class);
        $this->app->bind(EndorsementLetterRequestRepositoryInterface::class, EndorsementLetterRequestRepository::class);
        $this->app->bind(EndorseStudentRepositoryInterface::class, EndorseStudentRepository::class);
        $this->app->bind(OfficeRepositoryInterface::class, OfficeRepository::class);
        $this->app->bind(DocumentSubmissionRepositoryInterface::class, DocumentSubmissionRepository::class);
        $this->app->bind(LogRepositoryInterface::class, LogRepository::class);
        $this->app->bind(WeeklyRecordRepositoryInterface::class, WeeklyRecordRepository::class);


       

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
