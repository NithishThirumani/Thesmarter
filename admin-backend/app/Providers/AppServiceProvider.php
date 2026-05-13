<?php

namespace App\Providers;

use App\Channels\CustomDBChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use App\Repositories\UsersRepository;
use App\Repositories\AuthRepository;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AuthRepositoryInterface;
use App\Repositories\Contracts\DueRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\DueRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\RecipeRepository;
use App\Services\Charges\Strategies\ChargeStrategyResolver;
use App\Services\Discount\Strategies\Order\OrderDiscountStrategyResolver;
use App\Services\Discount\Strategies\Product\ProductDiscountStrategyResolver;
use App\Services\RecipeService;
use App\Modules\AdminAuth\Services\MailOtpSender;
use App\Modules\AdminAuth\Services\MockOtpSender;
use App\Modules\AdminAuth\Services\OtpSenderInterface;
use App\Support\Mail\PlatformMailConfigurator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind(UserRepositoryInterface::class, UsersRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(DueRepositoryInterface::class, DueRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->singleton(RecipeRepository::class);
        $this->app->singleton(RecipeService::class);
        $this->app->singleton(OrderDiscountStrategyResolver::class, function ($app) {
            return new OrderDiscountStrategyResolver();
        });
        $this->app->singleton(ProductDiscountStrategyResolver::class, function ($app) {
            return new ProductDiscountStrategyResolver();
        });
        $this->app->singleton(ChargeStrategyResolver::class,function ($app){
            return new ChargeStrategyResolver();
        });
        // Admin auth module: OTP via mail when ADMIN_OTP_VIA_MAIL=true, else log-only mock.
        $this->app->bind(OtpSenderInterface::class, function () {
            if (config('admin-auth.otp_via_mail')) {
                return new MailOtpSender;
            }

            return new MockOtpSender;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Notification::extend('customdb', function ($app) {
            return new CustomDBChannel();
        });

        PlatformMailConfigurator::apply();
    }
}
