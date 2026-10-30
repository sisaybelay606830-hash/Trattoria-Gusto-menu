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
        $branchCode = Request::get('branch', '');
        $tableRef   = Request::get('table', '');

        $branches = $this->branchRepo->getActive();
        $selectedBranch = null;

        if ($branchCode !== '') {
            $selectedBranch = $this->branchRepo->findByCode($branchCode);
        }

        View::render('feedback/index', [
            'branches'       => $branches,
            'selectedBranch' => $selectedBranch,
            'branchCode'     => $branchCode,
            'tableRef'       => $tableRef,
            'title'          => 'Customer Feedback — Trattoria Gusto',
            'csrf_token'     => Csrf::generate(),
            'errors'         => Session::flash('feedback_errors', []),
            'old'            => Session::flash('feedback_old', []),
        ]);
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
        $this->feedbackRepo->create([
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

        Response::redirect(base_url('feedback/thank-you'));
    }

    /**
     * Display thank-you confirmation page.
     * URL: GET /feedback/thank-you
     */
    public function thankYou(): void
    {
        View::render('feedback/success', [
            'title' => 'Thank You — Trattoria Gusto Feedback',
        ]);
    }
}

