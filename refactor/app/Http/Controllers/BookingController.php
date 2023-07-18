<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use DTApi\Http\Requests\IndexJobsRequest;
use DTApi\Http\Requests\ShowJobRequest;
use DTApi\Http\Requests\StoreBookingRequest;
use DTApi\Http\Requests\UpdateJobRequest;
use DTApi\Http\Requests\ImmediateJobEmailRequest;
use DTApi\Http\Requests\GetHistoryRequest;
use DTApi\Http\Requests\AcceptJobRequest;
use DTApi\Http\Requests\AcceptJobWithIdRequest;
use DTApi\Http\Requests\CancelJobRequest;
use DTApi\Http\Requests\EndJobRequest;
use DTApi\Http\Requests\CustomerNotCallRequest;
use DTApi\Http\Requests\GetPotentialJobsRequest;
use DTApi\Http\Requests\DistanceFeedRequest;
use DTApi\Http\Requests\ReopenRequest;
use DTApi\Http\Requests\ResendNotificationsRequest;
use DTApi\Http\Requests\ResendSMSNotificationsRequest;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * Get the jobs based on user ID or user type.
     *
     * @param IndexJobsRequest $request
     * @return mixed
     */
    public function index(IndexJobsRequest $request)
    {
        try {
            if ($user_id = $request->get('user_id')) {
                $response = $this->repository->getUsersJobs($user_id);
            } elseif (in_array($request->__authenticatedUser->user_type, [env('ADMIN_ROLE_ID'), env('SUPERADMIN_ROLE_ID')])) {
                $response = $this->repository->getAll($request);
            } else {
                throw new \Exception('Unauthorized access.');
            }

            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific job by ID.
     *
     * @param ShowJobRequest $request
     * @return mixed
     */
    public function show(ShowJobRequest $request)
    {
        try {
            $job = $this->repository->with('translatorJobRel.user')->find($request->id);

            if ($job) {
                return response($job);
            } else {
                throw new \Exception('Job not found.');
            }
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new booking.
     *
     * @param StoreBookingRequest $request
     * @return mixed
     */
    public function store(StoreBookingRequest $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->store($request->__authenticatedUser, $data);

            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a job by ID.
     *
     * @param $id
     * @param UpdateJobRequest $request
     * @return mixed
     */
    public function update($id, UpdateJobRequest $request)
    {
        try {
            $data = $request->validated();
            $cuser = $request->__authenticatedUser;
            $response = $this->repository->updateJob($id, $data, $cuser);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send immediate job email.
     *
     * @param ImmediateJobEmailRequest $request
     * @return mixed
     */
    public function immediateJobEmail(ImmediateJobEmailRequest $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->storeJobEmail($data);
            return response($response);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get job history for a user.
     *
     * @param GetHistoryRequest $request
     * @return mixed
     */
    public function getHistory(GetHistoryRequest $request)
    {
        try {
            if ($user_id = $request->get('user_id')) {
                $response = $this->repository->getUsersJobsHistory($user_id, $request);
            } else {
                $response = null;
            }

            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Accept a job.
     *
     * @param AcceptJobRequest $request
     * @return mixed
     */
    public function acceptJob(AcceptJobRequest $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;
            $response = $this->repository->acceptJob($data, $user);

            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Accept a job by ID.
     *
     * @param AcceptJobWithIdRequest $request
     * @return mixed
     */
    public function acceptJobWithId(AcceptJobWithIdRequest $request)
    {
        try {
            $data = $request->get('job_id');
            $user = $request->__authenticatedUser;
            $response = $this->repository->acceptJobWithId($data, $user);

            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel a job.
     *
     * @param CancelJobRequest $request
     * @return mixed
     */
    public function cancelJob(CancelJobRequest $request)
    {
        try {
            $data = $request->all();
            $user = $request->__authenticatedUser;
            $response = $this->repository->cancelJobAjax($data, $user);

            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * End a job.
     *
     * @param EndJobRequest $request
     * @return mixed
     */
    public function endJob(EndJobRequest $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->endJob($data);
            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Mark customer as not called for a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function customerNotCall(Request $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->customerNotCall($data);

        return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get potential jobs for a user.
     *
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        try {
            $user = $request->__authenticatedUser;
            $response = $this->repository->getPotentialJobs($user);
            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update distance and time feed for a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function distanceFeed(Request $request)
    {
        try {
            $data = $request->all();
            $jobid = $data['jobid'];

            $distance = $data['distance'] ?? "";
            $time = $data['time'] ?? "";
            $session = $data['session_time'] ?? "";
            $flagged = $data['flagged'] === 'true' && $data['admincomment'] !== '' ? 'yes' : 'no';
            $manually_handled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
            $by_admin = $data['by_admin'] === 'true' ? 'yes' : 'no';
            $admincomment = $data['admincomment'] ?? "";

            if ($time || $distance) {
                Distance::where('job_id', '=', $jobid)->update(['distance' => $distance, 'time' => $time]);
            }

            if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
                Job::where('id', '=', $jobid)->update([
                    'admin_comments' => $admincomment,
                    'flagged' => $flagged,
                    'session_time' => $session,
                    'manually_handled' => $manually_handled,
                    'by_admin' => $by_admin
                ]);
            }

            return response('Record updated!');

        } 
        catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function reopen(Request $request)
    {
        try {
            $data = $request->all();
            $response = $this->repository->reopen($data);
            return response($response);

        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function resendNotifications(Request $request)
    {
        try {
            $data = $request->all();
            $job = $this->repository->find($data['jobid']);
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendNotificationTranslator($job, $job_data, '*');
            return response(['success' => 'Push sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
       
        try {
            $data = $request->all();
            $job = $this->repository->find($data['jobid']);
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()]);
        }
    }

}
