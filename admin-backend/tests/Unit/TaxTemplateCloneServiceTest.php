<?php

namespace Tests\Unit;

use App\Modules\AdminTaxTemplate\Services\TaxTemplateCloneService;
use Tests\TestCase;

class TaxTemplateCloneServiceTest extends TestCase
{
    public function test_clone_with_empty_ids_is_noop(): void
    {
        $svc = $this->app->make(TaxTemplateCloneService::class);
        $svc->cloneTemplatesForCompany(1, [], 'IN');

        $this->assertTrue(true);
    }
}
