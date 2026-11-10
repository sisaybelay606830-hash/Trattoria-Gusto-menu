<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\{View, Request, Response};
use App\Repositories\{ProductRepository, CategoryRepository, BranchRepository, SettingsRepository};
use App\Services\AutoSyncService;

class MenuController
{
    public function index(): void
    {
        // Automatically synchronize any changes from CNET ERP staging in the background (throttled)
        AutoSyncService::checkAndSync();

        $settings  = (new SettingsRepository())->getAll();
        $maintenance = ($settings['maintenance_mode']['value'] ?? '0') === '1';

        if ($maintenance) {
            View::render('menu/maintenance', [
                'title' => 'Menu Temporarily Unavailable',
            ], 'base');
            return;
        }

        // Branch from query string or session
        $rawBranch = Request::str('branch');
        $branchRepo = new BranchRepository();
        $branch = null;

        if ($rawBranch !== '') {
            $branch = $branchRepo->resolveBranch($rawBranch);
            $branchCode = $branch ? $branch['code'] : $rawBranch;
            \App\Helpers\Session::set('current_branch_code', $branchCode);
        } else {
            $branchCode = (string)\App\Helpers\Session::get('current_branch_code', '');
            if ($branchCode !== '') {
                $branch = $branchRepo->resolveBranch($branchCode);
            }
        }

        // Categories
        $categories = (new CategoryRepository())->getParentCategoriesWithChildren();

        // Product filters
        $filters = ['is_visible' => 1, 'is_active_source' => 1];
        $search = Request::str('q');
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $parentFilter = Request::int('parent');
        if ($parentFilter > 0) {
            $filters['parent_category_id'] = $parentFilter;
        }
        $childFilter = Request::int('child');
        if ($childFilter > 0) {
            $filters['child_category_id'] = $childFilter;
        }

        $products   = (new ProductRepository())->findAll($filters, 1, 9999);
        $showPrices = ($settings['show_prices']['value'] ?? '0') === '1';

        $title = e($settings['menu_title']['value'] ?? 'Trattoria Gusto')
               . ' — '
               . e($settings['menu_subtitle']['value'] ?? 'Digital Menu');

        View::render('menu/index', [
            'title'      => $title,
            'categories' => $categories,
            'products'   => $products,
            'branch'     => $branch,
            'settings'   => $settings,
            'showPrices' => $showPrices,
            'search'     => $search,
        ], 'base');
    }

    public function branch(string $branch): void
    {
        // Validate branch code format
        if (!preg_match('/^[a-zA-Z0-9_\-]{1,50}$/', $branch)) {
            Response::abort(404, '404 — Branch Not Found');
        }
        $b = (new BranchRepository())->findByCode($branch);
        if (!$b || !(bool)$b['is_active']) {
            Response::abort(404, '404 — Branch Not Found');
        }
        // Canonical redirect to /menu?branch=CODE
        Response::redirect(base_url('menu') . '?branch=' . urlencode($branch));
    }
}

