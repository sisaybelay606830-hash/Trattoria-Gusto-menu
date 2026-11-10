<?php
/**
 * Feedback Controller
 * Handles customer feedback submissions from QR codes or direct links.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Repositories\BranchRepository;
use App\Repositories\FeedbackRepository;
use App\Repositories\SettingsRepository;

class FeedbackController
{
    private FeedbackRepository $feedbackRepo;
    private BranchRepository $branchRepo;

    public function __construct()
    {
        $this->feedbackRepo = new FeedbackRepository();
        $this->branchRepo   = new BranchRepository();
    }

    /**
     * Display the feedback form.
     * URL: GET /feedback?branch=xyz&table=4
     */
    public function form(): void
    {
        $rawBranch = Request::get('branch', '');
        $tableRef  = Request::get('table', '');

        $branches = $this->branchRepo->getActive();
        $selectedBranch = null;

        // 1. HIGHEST PRIORITY: Explicit branch from scanned QR code parameter
        if ($rawBranch !== '') {
            $selectedBranch = $this->branchRepo->resolveBranch($rawBranch);
            if ($selectedBranch) {
                // Scanned QR explicitly defined this branch — remember in session
                Session::set('current_branch_code', $selectedBranch['code']);
            }
        }

        // 2. SECOND PRIORITY: Current branch from session (if user scanned a menu QR earlier)
        if (!$selectedBranch) {
            $sessionBranch = (string)Session::get('current_branch_code', '');
            if ($sessionBranch !== '') {
                $selectedBranch = $this->branchRepo->resolveBranch($sessionBranch);
            }
        }

        // 3. THIRD PRIORITY: Default branch configured in Admin Settings
        if (!$selectedBranch) {
            $defaultCode = (new SettingsRepository())->get('default_branch_code', '');
            if (!empty($defaultCode)) {
                $selectedBranch = $this->branchRepo->resolveBranch($defaultCode);
            }
        }

        // 4. FOURTH PRIORITY: Default to 4 Kilo branch
        if (!$selectedBranch) {
            $selectedBranch = $this->branchRepo->resolveBranch('4_KILO');
        }

        // 5. If 4 Kilo does not exist in DB yet, create it
        if (!$selectedBranch) {
            try {
                $newId = $this->branchRepo->save([
                    'code'      => '4_KILO',
                    'name'      => 'Trattoria Gusto',
                    'address'   => 'Addis Ababa, Ethiopia',
                    'phone'     => '+251 111 23 45 67',
                    'is_active' => 1
                ]);
                $selectedBranch = $this->branchRepo->findById($newId);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // 6. Final safety net: first active branch
        if (!$selectedBranch) {
            $selectedBranch = !empty($branches) ? $branches[0] : [
                'id'   => 1,
                'code' => '4_KILO',
                'name' => 'Trattoria Gusto',
            ];
        }

        $settings = (new SettingsRepository())->getAll();

        View::render('feedback/index', [
            'branches'       => $branches,
            'selectedBranch' => $selectedBranch,
            'branch'         => $selectedBranch,
            'settings'       => $settings,
            'branchCode'     => $selectedBranch['code'] ?? '',
            'tableRef'       => $tableRef,
            'title'          => 'Customer Feedback — Trattoria Gusto',
            'csrf_token'     => Csrf::generate(),
            'errors'         => Session::flash('feedback_errors', []),
            'old'            => Session::flash('feedback_old', []),
        ], 'base');
    }

    /**
     * Process feedback submission.
     * URL: POST /feedback
     */
    public function submit(): void
    {
        Csrf::validateOrAbort(Request::post('csrf_token', ''));

        $branchId      = Request::post('branch_id', '');
        $tableNumber   = trim((string)Request::post('table_number', ''));
        $ratingOverall = (int)Request::post('rating_overall', 5);
        $ratingCoffee  = Request::post('rating_coffee', '');
        $ratingFood    = Request::post('rating_food', '');
        $ratingService = Request::post('rating_service', '');
        $comment       = trim((string)Request::post('comment', ''));
        $customerName  = trim((string)Request::post('customer_name', ''));
        $customerPhone = trim((string)Request::post('customer_phone', ''));

        // Fallback for branchId if empty
        if (empty($branchId)) {
            $branchCode = Request::post('branch_code', '');
            if (!empty($branchCode)) {
                $b = $this->branchRepo->findByCode($branchCode);
                if ($b) {
                    $branchId = (string)$b['id'];
                }
            }
            if (empty($branchId)) {
                $kilo = $this->branchRepo->findByCode('4_KILO');
                if ($kilo) {
                    $branchId = (string)$kilo['id'];
                }
            }
        }

        // Basic validation
        $errors = [];
        if ($ratingOverall < 1 || $ratingOverall > 5) {
            $errors['rating_overall'] = 'Please select an overall rating from 1 to 5 stars.';
        }

        if (strlen($comment) > 2000) {
            $errors['comment'] = 'Comments must not exceed 2000 characters.';
        }

        if (!empty($customerPhone) && !preg_match('/^[0-9+\s\-()]{7,25}$/', $customerPhone)) {
            $errors['customer_phone'] = 'Please provide a valid phone number or leave blank.';
        }

        if (!empty($errors)) {
            Session::flash('feedback_errors', $errors);
            Session::flash('feedback_old', Request::all());
            $redirectUrl = base_url('feedback');
            $branchCode = Request::post('branch_code', '');
            if ($branchCode !== '' || $tableNumber !== '') {
                $params = [];
                if ($branchCode !== '') $params['branch'] = $branchCode;
                if ($tableNumber !== '') $params['table'] = $tableNumber;
                $redirectUrl .= '?' . http_build_query($params);
            }
            Response::redirect($redirectUrl);
            return;
        }

        // Save feedback
        $feedbackId = $this->feedbackRepo->create([
            'branch_id'      => !empty($branchId) ? (int)$branchId : null,
            'table_number'   => $tableNumber ?: null,
            'rating_overall' => $ratingOverall,
            'rating_coffee'  => $ratingCoffee !== '' ? (int)$ratingCoffee : null,
            'rating_food'    => $ratingFood !== '' ? (int)$ratingFood : null,
            'rating_service' => $ratingService !== '' ? (int)$ratingService : null,
            'comment'        => $comment ?: null,
            'customer_name'  => $customerName ?: null,
            'customer_phone' => $customerPhone ?: null,
        ]);

        // Send instant notification to Telegram group if enabled
        try {
            $branchObj = !empty($branchId) ? $this->branchRepo->findById((int)$branchId) : null;
            \App\Services\TelegramService::sendFeedbackNotification([
                'branch_name'    => $branchObj['name'] ?? null,
                'table_number'   => $tableNumber ?: null,
                'rating_overall' => $ratingOverall,
                'rating_coffee'  => $ratingCoffee !== '' ? (int)$ratingCoffee : null,
                'rating_food'    => $ratingFood !== '' ? (int)$ratingFood : null,
                'rating_service' => $ratingService !== '' ? (int)$ratingService : null,
                'comment'        => $comment ?: null,
                'customer_name'  => $customerName ?: null,
                'customer_phone' => $customerPhone ?: null,
            ]);
        } catch (\Throwable $e) {
            \App\Helpers\Logger::error('FeedbackController: Telegram notify failed — ' . $e->getMessage());
        }

        Response::redirect(base_url('feedback/thank-you'));
    }

    /**
     * Display thank-you confirmation page.
     * URL: GET /feedback/thank-you
     */
    public function thankYou(): void
    {
        $settings = (new SettingsRepository())->getAll();
        View::render('feedback/success', [
            'title'    => 'Thank You — Trattoria Gusto Feedback',
            'settings' => $settings,
        ], 'base');
    }
}

