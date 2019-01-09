<?php

namespace App\Http\Controllers\Website;

use App\Helper;
use App\Http\Controllers\Website\WebsiteController;
use App\Models\AddressBookGroup;
use App\Models\ArchivedPhonenumber;
use App\Models\Campaign;
use App\Models\Phone;
use App\Models\Phonenumber;
use App\Models\PhonenumberAction;
use App\Models\UserSmsCost;
use App\Services\Cache\UserDataRedisCacheService;
use App\Services\CampaignDbService;
use App\Services\CampaignService;
use App\Services\FileService;
use App\Services\MessageLogsService;
use App\Services\MessageValidationService;
use App\Services\NotificationService;
use App\Services\SlackNotificationService;
use App\Services\UserService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignsController extends WebsiteController
{

    /**
     * Create a new instance of CampaignsController class
     *
     * @return void
     */
    public function __construct()
    {
        $this->activityLogRepo = new \App\Services\ActivityLogService();
        $this->notificationRepo = new NotificationService();
        $this->middleware('access.beta', ['only' => ['postCreateCampaign', 'postBatchSend']]);
    }

    /**
     * Get campaign by id.
     * GET /campaigns/show-campaign/{id}
     *
     * @param integer $id
     * @return JSON
     */
    public function getShowCampaign($id, $allStatuses = false)
    {
        $user = Auth::user();
        $allGroups = [];

        $campaign = Campaign::where('_id', $id)
            ->whereIn('status', ['dialing_completed',
                'saved', 'scheduled', 'schedulation_idle', 'stop','stopped_low_balance'])
            ->with(['schedulations', 'previews', 'groups' => function ($group) {
                $group->with('contactCount');
            } /*, 'phonenumbers', 'archivedPhonenumbers'  => function($query){
        $query->where('status', 'IN_PROGRESS');
        }*/, 'voiceFile', 'callbackFile', 'doNotCallFile'])->first();


        if ($campaign && $campaign->groups->isEmpty()) {

            $phoneNumbers = Phonenumber::where('campaign_id', $id)->with('actionsLog')->get();
            $archivedPhoneNumbers = ArchivedPhonenumber::where('campaign_id', $id)->with('actionsLog')->get();
            if (!$phoneNumbers->isEmpty()) {
                $phoneNumbers = $phoneNumbers->map(function ($phoneNumber) {
                    $phoneNumber->phone_no = "+" . $phoneNumber->phone_no;
                    return $phoneNumber;
                });
            }
            if (!$archivedPhoneNumbers->isEmpty()) {
                $archivedPhoneNumbers = $archivedPhoneNumbers->map(function ($archivedPhoneNumber) {
                    $archivedPhoneNumber->phone_no = "+" . $archivedPhoneNumber->phone_no;
                    return $archivedPhoneNumber;
                });
            }
            $phoneNumbers = $phoneNumbers->toArray();
            $archivedPhoneNumbers = $archivedPhoneNumbers->toArray();

        } else {
            $phoneNumbers = [];
            $archivedPhoneNumbers = [];
        }


        if (!$campaign) {
            $response = $this->createBasicResponse(-1, 'campaign_does_not exist_or_does_not_belong ');
        } else {
            if ($campaign->voiceFile) {
                $campaign['audio_amazon_s3'] = $this->getAmazonS3Url($campaign->voiceFile->map_filename);
            }

            if ($campaign->groups->isEmpty() && $campaign->status == 'saved' && !count($phoneNumbers) && !count($archivedPhoneNumbers)) {
                $allGroups = AddressBookGroup::whereNotNull("address_book_groups.name")->where('address_book_groups.user_id', $user->_id)->with('contactCount')->get()->toArray();
            }

            $campaign = $campaign->toArray();
            $campaign['phonenumbers'] = array_merge($phoneNumbers, $archivedPhoneNumbers);
            $campaign['allGroups'] = $allGroups;
            $campaign['transfer_options'] = $campaign['transfer_option'];
            $campaign = collect($campaign);

            $response = [
                'error' => [
                    'no' => 0,
                    'message' => 'campaign_data_2',
                ],
                'campaign' => $campaign,
            ];
        }

        return response()->json(['resource' => $response]);
    }

    /**
     * Display a listing of the resource.
     * GET /campaigns/index-campaigns
     *
     * @param Request $request
     * @return JSON
     */
    public function getIndexCampaigns(Request $request, CampaignService $campaignRepo)
    {
        $user = Auth::user();
        $orderField = $request->get('order_field', 'updated_at');
        $order = $request->get('order', 'DESC');
        $statuses = $request->get('checkbox');
        $dateRange = $request->get('date_range');
        $page = $request->get('page', 0);
        $requestType = $request->get('type', 'all');
        $perPage = 7;
        $searchName = $request->get('search_name');
        $userCampaigns = Campaign::where('user_id', $user->_id);

        $select = [
            'campaigns._id', 'campaigns.campaign_name', 'campaigns.caller_id', 'campaigns.user_id',
            'campaigns.transfer_digit', 'campaigns.campaign_voice_file_id', 'campaigns.callback_digit',
            'campaigns.replay_digit', 'campaigns.do_not_call_digit', 'campaigns.playback_count', 'campaigns.schedulation_original_data',
            'campaigns.repeat_batch_grouping', 'campaigns.grouping_type', 'campaigns.last_called',
            'campaigns.get_email_notifications', 'campaigns.should_use_all_contacts',
            'campaigns.retained_balance', 'campaigns.retained_gift_balance', 'campaigns.retries',
            'campaigns.status', 'campaigns.remaining_repeats', 'campaigns.first_scheduled_date',
            'campaigns.timezone', 'campaigns.created_at', 'campaigns.updated_at', 'campaigns.is_first_run',
            'campaigns.amount_spent', 'campaigns.is_archived', 'campaigns.sms_text', 'campaigns.type',
        ];

        $totalCampaignsCount = $userCampaigns->count();

        $statuses = json_decode($statuses, "[]");
        $campaigns = $userCampaigns
            ->with(['callsCount', 'archivedCallsCount', 'smsCount'])
            // ->select(['voiceFile._id'])
            ->where('is_pre_listen', 0)
            ->whereNull('api_key_id')
            ->whereNull('parent_id')
            ->where('is_prototype', 0);

        if ($requestType != 'all') {
            $campaigns = $campaigns->where('type', $requestType);
        }

        if ($dateRange) {
            $dateRangeTime = Carbon::now()->subDays($dateRange);
            $campaigns = $campaigns->where('created_at', '>', $dateRangeTime);
        }

        if ($searchName) {
            $campaigns = $campaigns->where(function ($query) use ($searchName) {
                $query->where('campaign_name', 'LIKE', '%' . $searchName . '%')
                    ->orWhere('caller_id', 'LIKE', '%' . $searchName . '%');
            });
        }

        if (count($statuses) > 0) {
            if (in_array('scheduled', $statuses)) {
                array_push($statuses, 'schedulation_idle');
                array_push($statuses, 'schedulation_in_progress');
            }
            $campaigns = $campaigns->whereIn('status', $statuses);
        } else {
            $statuses = [
                'dialing_completed',
                'start',
                'saved',
                'scheduled',
                'schedulation_in_progress',
                'schedulation_idle',
                'stop',
                'stopped_low_balance'
            ];
            $campaigns = $campaigns->whereIn('status', $statuses);
        }

        $count = $campaigns->count();

        $campaigns = $campaigns->select($select)->with(
            [
                'successPhonenumbers',
                'totalPhonenumbers',
                'previews',
                'archivedTotalPhonenumbers',
                'archivedSuccessPhonenumbers',
                // 'costPhonenumbers',
                // 'phonenumbers' => function ($query) {
                //     $query->select([
                //         '_id', 'campaign_id', 'phone_no',
                //     ]);
                // },
                'voiceFile' => function ($query) {
                    $query->select([
                        '_id', 'type', 'tts_text',
                        'length', 'orig_filename',
                        'user_id', 'map_filename',
                    ]);
                },
                // 'prototype' => function ($query) {
                //     $query->select([
                //         '_id',
                //     ]);
                // },
                'schedulations' => function ($query) {
                    $query->select([
                        '_id', 'campaign_id',
                        'recipients', 'scheduled_date', 'calls_limit', 'is_finished',
                    ])->where('is_finished', 0);
                },
            ]);

        $campaigns = $campaigns
            ->skip($page * $perPage)->take($perPage);

        /*if ($orderField == "cost") {
        // $select[] = \DB::raw("(SUM(billings.billed_from_gift) + SUM(billings.billed_from_purchased)) as cost");
        // $campaigns = $campaigns->select($select)->leftJoin('billings', 'billings.campaign_id', '=', 'campaigns._id')->groupBy("billings.campaign_id")->orderBy($orderField, $order);

        } else*/
        if ($orderField != 'max_cost') {
            $campaigns = $campaigns->orderBy($orderField, $order);
        }

        $campaigns = $campaigns->get();
        //dd($campaigns);
        $newCampaigns = [];
        foreach ($campaigns as $campaign) {
            // if($campaign->voiceFile) {
            //     $campaign['audio_amazon_s3'] = $this->getAmazonS3Url($campaign->voiceFile->map_filename);
            // }
            $campaign->totalRecipientsIfGroups = $campaignRepo->getTotalRecipientsIfGroups($campaign);


            foreach ($campaign->schedulations as $schedulation) {
                // dd($schedulation->scheduled_date);
                $schedulation->scheduled_date = Carbon::createFromFormat('Y-m-d H:i:s', $schedulation->scheduled_date)->toDateTimeString();
            }

            $campaign = $campaign->toArray();


            $campaign['interactions'] = [
                'count' => 0,
                'cost' => 0
            ];

            if ($campaign['is_archived']) {
                $phoneNumbersIds = $user->archivedPhonenumbers()->where('campaign_id', $campaign['_id'])->lists('original_id');
                $interactionsData = DB::table('archived_phonenumber_actions')
                    ->select([
                        \DB::raw("(SUM(billings.billed_from_gift) + SUM(billings.billed_from_purchased)) as cost"),
                        \DB::raw("COUNT(archived_phonenumber_actions._id) as count")
                    ])
                    ->leftJoin('billings', 'billings.archived_phonenumber_actions_id', '=', 'archived_phonenumber_actions.original_id')
                    ->whereIn('archived_phonenumber_actions.phonenumber_id', $phoneNumbersIds)
                    ->whereIn('archived_phonenumber_actions.call_status', ['TRANSFER_REQUESTED','CALLBACK_REQUESTED','REPLAY_REQUESTED','DONOTCALL_REQUESTED'])
                    ->get();
                if(count($interactionsData)) {
                    $campaign['interactions']['count'] = $interactionsData[0]->count;
                    $campaign['interactions']['cost'] = $interactionsData[0]->cost;
                }
            } else {
                $phoneNumbersIds = $user->phonenumbers()->where('campaign_id', $campaign['_id'])->lists('_id');
                $interactionsData = DB::table('phonenumber_actions')
                    ->select([
                        \DB::raw("(SUM(billings.billed_from_gift) + SUM(billings.billed_from_purchased)) as cost"),
                        \DB::raw("COUNT(phonenumber_actions._id) as count")
                    ])
                    ->leftJoin('billings', 'billings.phonenumber_action_id', '=', 'phonenumber_actions._id')
                    ->whereIn('phonenumber_actions.phonenumber_id', $phoneNumbersIds)
                    ->whereIn('phonenumber_actions.call_status', ['TRANSFER_REQUESTED','CALLBACK_REQUESTED','REPLAY_REQUESTED','DONOTCALL_REQUESTED'])
                    ->get();
                if(count($interactionsData)) {
                    $campaign['interactions']['count'] = $interactionsData[0]->count;
                    $campaign['interactions']['cost'] = $interactionsData[0]->cost;
                }
            }

            // merge archived with other records
            $campaign['success_phonenumbers'] = array_merge($campaign['success_phonenumbers'], $campaign['archived_success_phonenumbers']);
            $campaign['total_phonenumbers'] = array_merge($campaign['total_phonenumbers'], $campaign['archived_total_phonenumbers']);
            $campaign['calls_count'] = array_merge($campaign['calls_count'], $campaign['archived_calls_count']);
            $campaign['will_retry_at'] = null;

            if ($campaign['status'] == 'start' && count($campaign['total_phonenumbers']) &&
                (!count($campaign['success_phonenumbers']) || $campaign['success_phonenumbers'][0]['count'] < $campaign['total_phonenumbers'][0]['count']) &&
                count($campaign['calls_count']) && ($campaign['calls_count'][0]['count'] >= $campaign['total_phonenumbers'][0]['count'])
            ) {
                $firstUndelivered = Phonenumber::where('campaign_id', $campaign['_id'])->where('status', 'IN_PROGRESS')->whereNotNull('locked_at')->orderBy('locked_at', 'ASC')->first();
                $campaign['will_retry_at'] = Carbon::createFromFormat('Y-m-d H:i:s', $firstUndelivered->locked_at, config('app.timezone'))->addMinutes(15);
            }

            $newCampaigns[] = collect($campaign);
        }
        //dd($newCampaigns);
        $response = [
            'error' => [
                'no' => 0,
                'message' => 'campaigns_of_the_user',
            ],
            'campaigns' => $newCampaigns,
            'server_timezone' => config('app.timezone'),
            'page' => $page + 1,
            /*'total_cost' => $sum,*/
            'campaigns_count' => $count,
            'total_campaigns_count' => $totalCampaignsCount,
        ];

        return response()->json(['resource' => $response]);
    }

    /**
     * Get count of undelivered and failed phone numbers
     * GET /campaigns/undelivered-count
     *
     * @param Request $request
     * @return JSON
     */
    public function getRetryUndelivered(Request $request, $id)
    {
        $undeliveredCount = Phonenumber::where('campaign_id', $id)->whereNotIn('status', ['IDLE', 'SUCCEED'])
            ->where('retries', '>', 0)->count();
        $undeliveredCount += ArchivedPhonenumber::where('campaign_id', $id)->whereNotIn('status', ['IDLE', 'SUCCEED'])
            ->where('retries', '>', 0)->count();

        $neverCalledCount = Phonenumber::where('campaign_id', $id)->where('retries', 0)->count();
        $neverCalledCount += ArchivedPhonenumber::where('campaign_id', $id)->where('retries', 0)->count();

        $response = [
            'error' => [
                'no' => 0,
                'message' => '',
            ],
            'undelivered' => $undeliveredCount,
            'neverCalled' => $neverCalledCount,
            'undeliveredAndNeverCalled' => $neverCalledCount + $undeliveredCount,

        ];
        return response()->json(['resource' => $response]);
    }

    /**
     * Create group for undelivered/never called
     * POST /campaigns/create-group-for-undelivered
     *
     * @param Request $request
     * @return JSON
     */
    public function postCreateGroupForUndelivered(Request $request)
    {
        $user = \Auth::user();
        $messageId = $request->get('message_id');
        $condition = $request->get('condition');

        $campaign = Campaign::where('_id', $messageId)->first();

        if ($campaign->is_archived) {
            $phonenumbers = ArchivedPhonenumber::where('campaign_id', $messageId)
                ->where('user_id', $user->_id);
        } else {
            $phonenumbers = Phonenumber::where('campaign_id', $messageId)
                ->where('user_id', $user->_id);
        }

        switch ($condition) {
            case 'undelivered':
                $phonenumbers = $phonenumbers->whereNotIn('status', ['IDLE', 'SUCCEED'])
                    ->where('retries', '>', 0);
                break;
            case 'neverCalled':
                $phonenumbers = $phonenumbers->where('retries', 0);
                break;
            case 'undeliveredAndNeverCalled':
                $phonenumbers = $phonenumbers->whereNotIn('status', ['SUCCEED']);
                break;
        }

        $currentGroup = AddressBookGroup::create([
            'name' => $campaign->campaign_name,
            'type' => 'CREATED_ON_RETRY_UNDELIVERED',
            'user_id' => $user->_id,
        ]);

        $groupObjectId = $currentGroup->_id;

        $data = [];

        $importationId = mt_rand(100000, 999999);

        $phonenumbers->select(['_id', 'tariff_id', 'phone_no'])->with(['tariff' => function ($query) {
            $query->select('_id');
        }])->chunk(300, function ($phonenumbers) use ($groupObjectId, $user, &$data, $importationId) {


            foreach ($phonenumbers as $phonenumber) {
                $data[] = [
                    'user_id' => $user->_id,
                    'group_id' => null,
                    'tariff_id' => $phonenumber->tariff->_id,
                    'file_importation_id' => $importationId,
                    'phone_number' => $phonenumber->phone_no,
                    'name' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
        });


        \DB::table('temp_contacts')->insert($data);

        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

        DB::statement(DB::raw("call CALLBURN_PARSECONTACTS({$importationId}, 0, {$groupObjectId},0)"));

        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

//        $data = array_chunk($data,100,true);
//
//        foreach ($data as $chunk) {
//            \DB::table('temp_contacts')->insert($chunk);
//            \DB::statement("call CALLBURN_PARSECONTACTS({$chunk[0]['file_importation_id']}, 0, {$groupObjectId})");
//        }

        $response = [
            'error' => [
                'no' => 0,
                'message' => '',
            ],
            'group_id' => $groupObjectId,
        ];

        return response()->json(['resource' => $response]);
    }

    /**
     * Create calling interval of campaign.
     * @param Campaign $campaign
     */
    private function createCallingInterval(&$campaign)
    {
        $user = \Auth::user();
        $serverTimezone = config('app.timezone');
        $schedulationsToConsider = $campaign->schedulations()
            ->where('is_finished', 0)->get();
        $customerTimezone = $user->timezone;
        if (count($schedulationsToConsider) == 0) {
            if ($campaign->status == 'start') {
                $campaign->phonenumbers()
                    ->whereNull('schedulation_id')
                    ->where(['status' => 'IDLE'])
                    ->chunk(500, function ($phoneNumbers) {
                        $phoneNumbersIds = [];
                        foreach ($phoneNumbers as $phoneNumber) {
                            $phoneNumbersIds[] = $phoneNumber->_id;
                        }
                        Phonenumber::whereIn('_id', $phoneNumbersIds)
                            ->update(['status' => 'IN_PROGRESS']);
                    });
            }
            return;
        }
        foreach ($schedulationsToConsider as $schedulation) {
            $callsLimit = $schedulation->calls_limit;
            $deliverySpeed = $schedulation->delivery_speed;
            $scheduledDate = $schedulation->scheduled_date;
            $toBeCalledAtInServerTime = Carbon::createFromFormat('Y-m-d H:i:s', $scheduledDate, $customerTimezone)->setTimezone($serverTimezone);
            if (!$deliverySpeed) {
                $updateStatus = $campaign->phonenumbers()
                    ->whereIn('status', ['IN_PROGRESS', 'IDLE'])
                    ->whereNull('schedulation_id')
                    ->orderBy('_id')
                    ->limit($callsLimit)
                    ->chunk(500, function ($phoneNumbers) use ($toBeCalledAtInServerTime, $schedulation) {
                        $phoneNumbersIds = [];
                        foreach ($phoneNumbers as $phoneNumber) {
                            $phoneNumbersIds[] = $phoneNumber->_id;
                        }
                        Phonenumber::whereIn('_id', $phoneNumbersIds)
                            ->update([
                                'to_be_called_at' => $toBeCalledAtInServerTime,
                                'schedulation_id' => $schedulation->_id,
                                'status' => 'IN_PROGRESS',
                            ]);
                    });
            } else {

                $values = ' ';
                $counter = 0;
                $phonenumbers = $campaign->phonenumbers()
                    ->whereIn('status', ['IN_PROGRESS', 'IDLE'])
                    ->whereNull('schedulation_id')
                    ->orderBy('_id')
                    ->limit($callsLimit)
                    ->select('_id');

                $phonenumbers->chunk(1000, function ($phoneNumbers) use ($toBeCalledAtInServerTime, $schedulation, &$values, $counter) {
                    foreach ($phoneNumbers as $key => $phoneNumber) {
                        $values .= "( " . $phoneNumber->_id . " ,'" . $toBeCalledAtInServerTime . "'),";
                        $counter++;
                        $toBeCalledAtInServerTime = $toBeCalledAtInServerTime->addSeconds($schedulation->calling_interval_minutes);

                    }

                    if ($counter) {
                        $values = rtrim($values, ',');
                        $statement = "INSERT INTO phonenumbers (_id,to_be_called_at) 
                                    VALUES " . $values . "
                                        ON DUPLICATE KEY UPDATE to_be_called_at = VALUES(to_be_called_at) , status = 'IN_PROGRESS' , schedulation_id = " . $schedulation->_id . " ;";
                        \DB::statement($statement);
                        $values = ' ';
                    }
                });
            }
        }
        $campaign->phonenumbers()->whereNull('schedulation_id')
            ->update(['status' => 'IDLE']);
    }

    /**
     * Create new campaign.
     * POST /campaigns/create-campaign
     *
     * @param Request $request
     * @param CampaignDbService $campaignDbRepo
     * @param CampaignService $campaignRepo
     * @param MessageLogsService $messageLogsService
     * @return JSON
     */
    public function postCreateCampaign(
        Request $request,
        CampaignDbService $campaignDbRepo,
        CampaignService $campaignRepo,
        MessageLogsService $messageLogsService
    )
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $userSmsTariffs = UserSmsCost::where('user_id', $user->_id)->get();
            $campaignStatus = $request->get('status');
            $isPreview = $request->get('is_preview');
            $parentId = $request->get('parent_id');
            $saveAsDraft = $campaignStatus == 'saved' ? true : false;
            $fileId = $request->get('campaign_voice_file_id');
            $serverTimezone = config('app.timezone');
            $userRepo = app(UserService::class);
            $validatedCampaignData = CampaignService::createCampaignValidation($request);

            if ($validatedCampaignData->error != 0) {
                $validatedCampaignData->save_as_draft = $saveAsDraft;
                $response = $this->createBasicResponse($validatedCampaignData->error, $validatedCampaignData->text);
                return response()->json(['resource' => $response]);
            }

            $finalPhonenumbers = $validatedCampaignData->finalPhonenumbers;
            $usersCallerId = $validatedCampaignData->usersCallerId;
            $finalSchedulation = $validatedCampaignData->finalSchedulation;
            $shouldUseAllContacts = $validatedCampaignData->shouldUseAllContacts;
            $groupIds = $validatedCampaignData->groupIds;
            $campaignData = $validatedCampaignData->campaign;
            $customerTimezone = $user->timezone ? $user->timezone : 'UTC';

            $phonenumbersToadd = [];
            $addedSchedulations = [];
            $recipentsCount = null;
            $maxGiftCost = 0;
            $maxCost = 0;

            if (!$campaignData['sender_name'] && !$campaignData['type'] != 'VOICE_CALL') {
                $campaignData['sender_name'] = 'CallBurn';
            }

            if ($parentId) {
                $campaignData['parent_id'] = $parentId;
            }

            if (count($finalPhonenumbers) > 0 && !$isPreview) {
                $groupObject = $user->addressBookGroups()->create([
                    'name' => $campaignData['campaign_name'] ? $campaignData['campaign_name'] : null,
                ]);
                $campaignData['created_group_id'] = $groupObject->_id;
            }

            $campaign = $campaignRepo->createCampaign($campaignData);
            $smsText = $campaign->sms_text;
            $file = $user->files()->find($fileId);
            $length = $file ? $file->length : 0;
            $length = ($length < 20) ? 20 : $length;
            $userCanAll = true;

            $cacheService = new UserDataRedisCacheService();
            $cacheService->incrementMessages($campaign->user_id, 1);

            if (!empty($finalSchedulation)) {
                $recipentsCount = array_sum(array_column($finalSchedulation, 'max'));
            }

            foreach ($finalSchedulation as $schedulation) {
                if (!isset($schedulation['date']) || !$schedulation['date'] || !isset($schedulation['max'])) {
                    continue;
                }

                $newSchedulationCallingIntervalMinutes = null;

                if (isset($schedulation['sending_time']) && $schedulation['sending_time']) {
                    $recipientsPerIteration = $schedulation['max'] > 1 ? $schedulation['max'] - 1 : 1;
                    $newSchedulationCallingIntervalMinutes = round($schedulation['sending_time'] * 60 / $recipientsPerIteration);
                }

                $newSchedulation = new \App\Models\Schedulation;
                $newSchedulation->campaign_id = $campaign->_id;
                $newSchedulation->scheduled_date = Carbon::createFromFormat('Y-m-d H:i:s', $schedulation['date'], $customerTimezone)->setTimezone($serverTimezone);
                $newSchedulation->calls_limit = $schedulation['max'];
                $newSchedulation->delivery_speed = isset($schedulation['sending_time']) ? $schedulation['sending_time'] : null;
                $newSchedulation->calling_interval_minutes = $newSchedulationCallingIntervalMinutes;
                $newSchedulation->recipients = isset($schedulation['max']) ? $schedulation['max'] : 0;
                $newSchedulation->save();

                $addedSchedulations[] = $newSchedulation;
            }

            $schedulationLimit = 0;
            $schedulationInterval = [
                'schedulation_id' => null,
                'interval' => null
            ];
            $usedSchedulationsIds = [];

            // if already have phonenumbers case
            foreach ($finalPhonenumbers as $phonenumber) {

                $cost = $phonenumber['tariff']['country']['customer_price'] * $length / 60;
                $smsCost = $phonenumber['tariff']['country']['sms_customer_price'] * ceil(strlen($smsText) / 160);
                if ($userSmsTariffs->count()) {
                    $currentCountryTariff = null;
                    foreach ($userSmsTariffs as $userSmsTariff) {
                        if ($userSmsTariff->country_id == $phonenumber['tariff']['country']['_id']) {
                            $currentCountryTariff = $userSmsTariff;
                        }
                    }
                    if ($currentCountryTariff) {
                        $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                    }
                }
                $userCanUseGift = false;
                $tariff = $phonenumber['tariff'];
                $finalNumber = $phonenumber['phonenumber'];
                $isUserNumberFromEu = isset($usersCallerId->tariff->country) ? $usersCallerId->tariff->country->is_eu_member : false;
                $isFree = $this->shouldPhonenumberBeFree($tariff, $usersCallerId, $campaign, $user);
                $isNumberFromEu = $tariff->country->is_eu_member;
                $isFromNotEuToEu = !$isUserNumberFromEu && $isNumberFromEu;
                $contactObject = $user->addressBookContacts()->where('phone_number', $finalNumber)->first();

                if ($campaign->type == 'VOICE_MESSAGE') {
                    $userCanUseGift = $userRepo->canUseGift($phonenumber, $user, $maxGiftCost, $cost);
                }

                if ($userCanUseGift) {
                    $maxGiftCost += $cost;
                } else {
                    if (!$user->bonus_criteria || $user->bonus_criteria > $phonenumber['tariff']['best_margin']) {
                        $userCanAll = false;
                    }
                    if ($campaign->type == 'VOICE_MESSAGE') {
                        $maxCost += $cost;
                    }
                    if ($campaign->type == 'SMS') {
                        $maxCost += $smsCost;
                    }
                    if ($campaign->type == 'VOICE_WITH_SMS') {
                        $maxCost += $smsCost + $cost;
                    }
                }

                if (!$contactObject && !$isPreview) {
                    $contactData = [
                        'phone_number' => $finalNumber,
                        'tariff_id' => $tariff['_id'],
                        'type' => $campaignDbRepo->checkIsPhoneNumberMobile($finalNumber),
                        'user_id' => $user->_id,
                    ];
                    try {
                        $contactObject = \App\Models\AddressBookContact::create($contactData);
                    } catch (\Exception $e) {
                        $contactObject = null;
                    }
                }
                if ($contactObject && !$isPreview) {
                    $contactObject->groups()->attach($groupObject->_id);
                }

                $remainingRetries = $isPreview ? 2 : 0;
                $schedulationToSet = null;

                foreach ($addedSchedulations as $addedSchedulation) {
                    if (!in_array($addedSchedulation->_id, $usedSchedulationsIds)) {
                        if ($schedulationLimit == $addedSchedulation->calls_limit) {
                            array_push($usedSchedulationsIds, $addedSchedulation->_id);
                            $schedulationLimit = 0;
                        } else {
                            $schedulationToSet = $addedSchedulation;
                        }
                    }
                }

                if ($schedulationToSet) {
                    if ($schedulationInterval['schedulation_id'] == $schedulationToSet->_id) {
                        if ($schedulationToSet->delivery_speed) {
                            $schedulationInterval['interval'] = $schedulationInterval['interval']->addSeconds($schedulationToSet->calling_interval_minutes);
                            $schedulationInterval['interval'] = Carbon::createFromFormat('Y-m-d H:i:s', $schedulationInterval['interval'], config('app.timezone'));
                        }
                    } else {
                        $schedulationInterval['schedulation_id'] = $schedulationToSet->_id;
                        $schedulationInterval['interval'] = Carbon::createFromFormat('Y-m-d H:i:s', $schedulationToSet->scheduled_date, $serverTimezone)->setTimezone($serverTimezone);
                    }

                    $schedulationLimit++;
                    $schedulationData = [
                        "schedulation_id" => $schedulationToSet->_id,
                        "status" => "IN_PROGRESS",
                        "to_be_called_at" => $schedulationInterval['interval'],
                    ];
                } else {
                    if (count($addedSchedulations)) {
                        if ($userCanUseGift) {
                            $maxGiftCost -= $cost;
                        } else {
                            if ($campaign->type == 'VOICE_MESSAGE') {
                                $maxCost -= $cost;
                            }

                            if ($campaign->type == 'SMS') {
                                $maxCost -= $smsCost;
                            }
                            if ($campaign->type == 'VOICE_WITH_SMS') {
                                $maxCost -= $cost;
                                $maxCost -= $smsCost;
                            }
                        }
                        $schedulationData = [
                            "schedulation_id" => null,
                            "status" => "IDLE",
                            "to_be_called_at" => null,
                        ];
                    } else {
                        $schedulationData = [
                            "schedulation_id" => null,
                            "status" => "IN_PROGRESS",
                            "to_be_called_at" => null,
                        ];
                    }
                }

                $shouldPutThreeAsterisks = false;
                if ($contactObject) {
                    $shouldPutThreeAsterisks = $contactObject->should_put_three_asterisks;
                }

                $phonenumbersToadd[] = [
                        'campaign_id' => $campaign->_id,
                        'action_type' => $campaign->type,
                        'phone_no' => $finalNumber,
                        'should_put_three_asterisks' => $shouldPutThreeAsterisks,
                        'created_at' => date('Y-m-d H:i:s'),
                        'retries' => $remainingRetries,
                        'tariff_id' => $tariff->_id,
                        'user_id' => $user->_id,
                        'type' => $campaignDbRepo->checkIsPhoneNumberMobile($finalNumber),
                        'is_from_not_eu_to_eu' => $isFromNotEuToEu,
                        'is_free' => $isFree,
                    ] + $schedulationData;
            }

            if ($maxCost > 0 && !$userCanAll) {
                $maxCost += $maxGiftCost;
                $maxGiftCost = 0;
            }

            if ($shouldUseAllContacts) {
                $recipentsCounter = 0;
                $contacts = $user->addressBookContacts();
                $contacts = $contacts->selectRaw('count(*) as count, tariff_id, user_id, tariffs.prefix, tariffs.country_id, tariffs.best_margin, countries.customer_price,countries.sms_customer_price')
                    ->groupBy('tariff_id')
                    ->leftJoin('tariffs', 'address_book_contacts.tariff_id', '=', 'tariffs._id')
                    ->leftJoin('countries', 'tariffs.country_id', '=', 'countries._id')
                    ->get();
                $minimumMargin = $user->bonus_criteria;
                $availableGiftBalance = $user->bonus;
                $maxCost = 0;
                $maxGiftCost = 0;
                $userCanAll = true;

                foreach ($contacts as $contact) {

                    $smsCost = $contact->sms_customer_price * ceil(strlen($smsText) / 160);
                    if ($userSmsTariffs->count()) {
                        $currentCountryTariff = null;
                        foreach ($userSmsTariffs as $userSmsTariff) {
                            if ($userSmsTariff->country_id == $contact->_id) {
                                $currentCountryTariff = $userSmsTariff;
                            }
                        }
                        if ($currentCountryTariff) {
                            $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                        }
                    }

                    if ($recipentsCount > $recipentsCounter || is_null($recipentsCount)) {

                        if (is_null($recipentsCount)) {
                            if ($campaign->type == 'VOICE_MESSAGE') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                            }
                            if ($campaign->type == 'VOICE_WITH_SMS') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                $tempCost += $smsCost;
                            }
                            if ($campaign->type == 'SMS') {
                                $tempCost = $smsCost;
                            }
                        } else {
                            if ($contact->count + $recipentsCounter < $recipentsCount) {
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            } else {
                                $count = $recipentsCount - $recipentsCounter;
                                $tempCost = $contact->customer_price * $length * $count / 60;
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            }
                            $recipentsCounter += $contact->count;
                        }

                        if ($contact->best_margin > $minimumMargin && $availableGiftBalance > 0 && $campaign->type == 'VOICE_MESSAGE') {
                            $leftAmount = $availableGiftBalance - $tempCost;
                            if ($leftAmount < 0) {
                                $maxGiftCost += $availableGiftBalance;
                                $availableGiftBalance = 0;
                                $maxCost += abs($leftAmount);
                            } else {
                                $maxGiftCost += $tempCost;
                                $availableGiftBalance -= $tempCost;
                            }
                        } else {
                            if ($contact->best_margin > $minimumMargin) {
                                $userCanAll = false;
                            }
                            $maxCost += $tempCost;
                        }
                    } else {
                        break;
                    }
                }
                if ($maxCost > 0 && !$userCanAll) {
                    $maxCost += $maxGiftCost;
                    $maxGiftCost = 0;
                }
            } elseif ($groupIds) {
                $recipentsCounter = 0;

                $campaign->groups()->attach($groupIds);
                $contacts = $user->addressBookContacts();
                $contacts = $contacts->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('address_book_groups._id', $groupIds);
                }, '>', 0);
                $contacts = $contacts->selectRaw('count(*) as count, tariff_id, user_id, tariffs.prefix, tariffs.country_id, tariffs.best_margin, countries.customer_price, countries.sms_customer_price')
                    ->groupBy('tariff_id')
                    ->leftJoin('tariffs', 'address_book_contacts.tariff_id', '=', 'tariffs._id')
                    ->leftJoin('countries', 'tariffs.country_id', '=', 'countries._id')
                    ->get();

                $minimumMargin = $user->bonus_criteria;
                $availableGiftBalance = $user->bonus;
                $maxCost = 0;
                $maxGiftCost = 0;
                $userCanAll = true;
                foreach ($contacts as $contact) {

                    $smsCost = $contact->sms_customer_price * ceil(strlen($smsText) / 160);
                    if ($userSmsTariffs->count()) {
                        $currentCountryTariff = null;
                        foreach ($userSmsTariffs as $userSmsTariff) {
                            if ($userSmsTariff->country_id == $contact->_id) {
                                $currentCountryTariff = $userSmsTariff;
                            }
                        }
                        if ($currentCountryTariff) {
                            $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                        }
                    }

                    if ($recipentsCount > $recipentsCounter || is_null($recipentsCount)) {
                        if (is_null($recipentsCount)) {
                            if ($campaign->type == 'VOICE_MESSAGE') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                            }
                            if ($campaign->type == 'VOICE_WITH_SMS') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                $tempCost += $smsCost;
                            }
                            if ($campaign->type == 'SMS') {
                                $tempCost = $smsCost;
                            }
                        } else {
                            if ($contact->count + $recipentsCounter < $recipentsCount) {
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            } else {
                                $count = $recipentsCount - $recipentsCounter;
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            }
                            $recipentsCounter += $contact->count;
                        }
                        if ($contact->best_margin > $minimumMargin && $availableGiftBalance > 0 && $campaign->type == 'VOICE_MESSAGE') {
                            $leftAmount = $availableGiftBalance - $tempCost;
                            if ($leftAmount < 0) {
                                $maxGiftCost += $availableGiftBalance;
                                $availableGiftBalance = 0;
                                $maxCost += abs($leftAmount);
                            } else {
                                $maxGiftCost += $tempCost;
                                $availableGiftBalance -= $tempCost;
                            }
                        } else {
                            if ($contact->best_margin > $minimumMargin) {
                                $userCanAll = false;
                            }
                            $maxCost += $tempCost;
                        }
                    } else {
                        break;
                    }
                }
                if ($maxCost > 0 && !$userCanAll) {
                    $maxCost += $maxGiftCost;
                    $maxGiftCost = 0;
                }
            } else {
                \App\Models\Phonenumber::insert($phonenumbersToadd);
                $campaign->is_first_run = 0;
            }

            if (count($addedSchedulations) > 0) {
                $schedulationDateFormat = $addedSchedulations[0]->scheduled_date->format('Y-m-d H:i:s');
                $campaign->first_scheduled_date = $addedSchedulations[0]->scheduled_date;
                $campaign->first_scheduled_date = Carbon::createFromFormat('Y-m-d H:i:s', $schedulationDateFormat, $serverTimezone)->format('Y-m-d H:i:s');
            }

            $campaign->is_pre_listen = $isPreview ? 1 : 0;
            $campaign->retained_balance = $maxCost;
            $campaign->retained_gift_balance = $maxGiftCost;
            $campaign->is_gift_being_used = ($maxGiftCost > 0) ? true : false;

            if ($isPreview && $user->balance == $user->bonus) {
                $campaign->is_gift_being_used = true;
            }

            $campaign->save();

            if ($campaignStatus === 'start') {
                $this->notificationRepo->createOnCampaignCreate($campaign);
            }

            $isAvailableBalance = (bool)($campaign->retained_balance + $campaign->retained_gift_balance <= $user->balance);

            if ($campaign->status == 'scheduled' && !$isAvailableBalance) {
                $this->notificationRepo->createCampaignNotEnoughBalance($campaign);
            }

            // Messages Log

            $type = 'INFO';
            $text = 'User with #' . $user->_id . ' id has created a new campaign!';

            $campaign = $campaign->with(['schedulations', 'groups', 'phonenumbers'])->find($campaign->_id);
            $messageLogsService->createMessageLogForCreate($type, $text, $campaign);

            $slackNotifyText = 'User ' . $user->email . ' Created campaign with estimated amount of ' . $maxCost . 'EUR and gift amount of ' . $maxGiftCost;

            if ($campaign->first_scheduled_date) {
                $slackNotifyText .= ' and scheduled at ' . $campaign->first_scheduled_date;
            }

            SlackNotificationService::notify($slackNotifyText);

            // ActivityLog
            $logData = [
                'campaign_id' => $campaign->_id,
                'user_id' => $user->_id,
                'device' => 'WEBSITE',
                'action' => 'MESSAGES',
                'description' => 'User created campaign',
            ];

            $this->activityLogRepo->createActivityLog($logData);

            DB::commit();

            $response = [
                'error' => [
                    'no' => 0,
                    'message' => 'Campaign__created',
                ],
                'campaign_id' => $campaign->_id,
            ];

            return response()->json(['resource' => $response]);
        } catch (\Exception $e) {

            DB::rollback();
            \Log::error($e);

            $response = [
                'error' => [
                    'no' => -2,
                    'message' => 'something__went__wrong',
                ],
                'message' => $e->getMessage(),

            ];

            return response()->json(['resource' => $response]);
        }
    }

    /**
     * Update campaign's caller-id.
     * POST /campaigns/update-campaign-caller-id
     *
     * @param integer $id
     * @param Request $request
     * @return JSON
     */
    public function postUpdateCampaignCallerId(Request $request, CampaignService $campaignRepo)
    {
        $user = Auth::user();
        $campaignId = $request->get('campaign_id');
        $callerId = $request->get('caller_id');
        $updateCallerId = $campaignRepo->updateCampaignCallerId($campaignId, $callerId);
        if ($updateCallerId) {
            $response = $this->createBasicResponse(0, 'campaign_caller_id_updated');
        } else {
            $response = $this->createBasicResponse(-1, 'something_went_wrong_on_caller_id_update');
        }

        return response()->json(['resource' => $response]);
    }

    /**
     * Update campaign.
     * POST /campaigns/update-campaign-name-caller-id
     *
     * @param integer $id
     * @param Request $request
     * @return JSON
     */
    public function postUpdateCampaignNameCallerId(Request $request, CampaignService $campaignRepo)
    {
        // dd($request->all());
        $updateData = $request->except(['schedulation_original_data', 'phonenumbers', 'schedulations']);
        $campaignId = $updateData['campaign_id'];
        $validData = $campaignRepo->basicValidationUpdateCampaign($updateData);
        //dd($validData);
        //dd($validData);
        if (is_object($validData) && isset($validData->error)) {
            $response = $this->createBasicResponse($validData->error, $validData->text);
        } else {
            $resp = $campaignRepo->updateCampaign($campaignId, $validData);
            $response = $this->createBasicResponse(0, 'updated_caller_id_and_name');
        }
        return response()->json(['resource' => $response]);
    }

    /**
     * Update campaign.
     * POST /campaigns/update-campaign
     *
     * @param integer $id
     * @param Request $request
     * @return JSON
     */
    public function postUpdateCampaign(
        Request $request,
        CampaignDbService $campaignDbRepo,
        CampaignService $campaignRepo,
        MessageLogsService $messageLogsService
    )
    {
        $user = Auth::user();
        $userSmsTariffs = UserSmsCost::where('user_id', $user->_id)->get();
        $userRepo = app(UserService::class);
        $validatedCampaignData = CampaignService::updateCampaignValidation($request);

        if ($validatedCampaignData->error != 0) {
            $response = $this->createBasicResponse($validatedCampaignData->error, $validatedCampaignData->text);
            return response()->json(['resource' => $response]);
        }
        $campaignData = $validatedCampaignData->campaignData;
        $smsText = $campaignData['sms_text'];
        $getEmailNotifications = $validatedCampaignData->getEmailNotifications;
        $campaignId = $validatedCampaignData->campaignId;
        $campaign = $validatedCampaignData->campaign;
        $oldCampaign = clone $campaign;

        $remainingRepeats = $validatedCampaignData->remainingRepeats;
        $finalPhonenumbers = $validatedCampaignData->finalPhonenumbers;
        $usersCallerId = $validatedCampaignData->usersCallerId;
        $finalSchedulation = $validatedCampaignData->finalSchedulation;
        $groupIds = $validatedCampaignData->groupIds;
        $shouldUseAllContacts = $campaignData['should_use_all_contacts'];

        if (!empty($finalSchedulation)) {
            $recipentsArray = array_column($finalSchedulation, 'max');
            $recipentsCount = array_sum($recipentsArray);
        } else {
            $recipentsCount = null;
        }

        if ($getEmailNotifications) {
            $campaignData['get_email_notifications'] = $request->get('get_email_notifications');
        }
        // $campaignData['get_email_notifications'] = $request->get('get_email_notifications');

       

        //Milutin Edit 21.12.2018 - Optimisation of trigger and updates
        $phone= new Phone();
        $first= $phone->result_first($campaignId);
        $second=$phone->result_second($campaignId);
        $third=$phone->result_third($campaignId);
        $fourth=$phone->result_fourth($campaignId);
        $firstfinal=$first->number;
        $secondfinal=$second->number;
        $thirdfinal=$third->number;
        $fourthfinal=$fourth->number;
        $datetime=Carbon::now();
        $finaldatetime=$datetime->toDateTimeString();
         
        $resp = $campaignRepo->updateCampaign($campaignId, $campaignData);
         
//        $update= DB::table('phonenumbers')
//                ->where("campaign_id", $campaignId)
//                 ->update([
//                'status' => 'IDLE',
//                'updated_at' => $finaldatetime,
//                'sch_no' => $firstfinal,
//                'idl_no' => $secondfinal,
//                'suc_no' => $thirdfinal,
//                'tot_no' => $fourthfinal
//            ]);
        
        
        $sch=DB::table("schedulations")
        ->select("_id")
        ->where('campaign_id','=',$campaignId)
        ->orderBy('scheduled_date','desc')
        ->get();
        
        foreach($sch as $sc){
            $result = DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('status','=','IN_PROGRESS')
                ->where('schedulation_id','=',$sc->_id)
                ->first();     	
            if ($result->number==0) {
                $update= DB::table('schedulations')
                ->where("_id", $sc->_id)
                ->update([
                'is_finished' => 1 
            ]);
            }
        }
        
        $sch2=DB::table("schedulations")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('campaign_id','=',$campaignId)
                ->where('is_finished','=',0)
                ->first();
        $ph=DB::table("phonenumbers")
                ->select(DB::raw('COUNT(*) as number'))
                ->where('campaign_id','=',$campaignId)
                ->where('idl_no','=',1)
                ->first();
        if($sch2->number==0 && $ph->number==0){
            $updatecampaign= DB::table('campaign')
                ->where("_id", $campaignId)
                 ->update([
                'status' => "dialing_complete", 
                'retained_balance' => 0,
                'retained_gift_balance' => 0,
                'updated_at' => $finaldatetime
            ]);
        }
        else {
            if($sch2->number==0){
            $updatecampaign= DB::table('campaign')
                ->where("_id", $campaignId)
                 ->update([
                'status' => "schedulation_idle", 
                
                'updated_at' => $finaldatetime
            ]);
     }
        else {
        $updatecampaign= DB::table('campaign')
                ->where("_id", $campaignId)
                 ->update([
                'status' => "scheduled", 
                'updated_at' => $finaldatetime
            ]);  
     }
 }
      
        
        //Millutin edit ENDS HERE 21.12.2018.
        
        $campaign->schedulations()->where('is_finished', 0)->delete();
        
   

        /************** Add schedulations and phonenumbers ***********/
        $schedulationsToAdd = [];
        $isFirstSchedule = true;
        foreach ($finalSchedulation as $schedulation) {
            if (!isset($schedulation['date']) || !isset($schedulation['max'])) {
                continue;
            }
            $now = Carbon::now();
            if ($now->gte($schedulation['date'])) {
                continue;
            }

            $schedData = [
                'campaign_id' => $campaign->_id,
                'scheduled_date' => $schedulation['date'],
                'calls_limit' => $schedulation['max'],
                'delivery_speed' => isset($schedulation['sending_time']) ? $schedulation['sending_time'] : null,
                'recipients' => isset($schedulation['max']) ? $schedulation['max'] : 0,
            ];

            if (isset($schedulation['sending_time']) && $schedulation['sending_time']) {
                $recipientsPerIteration = $schedulation['max'] > 1 ? $schedulation['max'] - 1 : 1;
                $schedData['calling_interval_minutes'] = round($schedulation['sending_time'] * 60 / $recipientsPerIteration);
            } else {
                $schedData['calling_interval_minutes'] = null;
            }

            $schedulationsToAdd[] = $schedData;
        }

        if ($campaign->status == 'saved') {
            if (count($finalPhonenumbers) > 0) {
                $groupObject = $user->addressBookGroups()->create([
                    'name' => $campaignData['campaign_name'] ? $campaignData['campaign_name'] : null,
                ]);
                $campaignData['created_group_id'] = $groupObject->_id;
            }
            \App\Models\AddressBookGroup::where('_id', $campaign->created_group_id)->delete();
            $campaign->phonenumbers()->where('status', 'IN_PROGRESS')->whereHas('calls', function ($query) {
            }, '=', 0)->delete();
            $phonenumbersToadd = [];

            $protoPhonenumbersToadd = [];

            $fileId = $request->get('campaign_voice_file_id');
            $file = $user->files()->find($fileId);

            $length = $file ? $file->length : 0;
            $length = ($length < 20) ? 20 : $length;

            $userCanAll = true;
            $maxCost = 0;
            $maxGiftCost = 0;

            foreach ($finalPhonenumbers as $phonenumber) {
                $cost = $phonenumber['tariff']['country']['customer_price'] * $length / 60;
                $smsCost = $phonenumber['tariff']['country']['sms_customer_price'] * ceil(strlen($smsText) / 160);
                $userCan = false;

                if ($userSmsTariffs->count()) {
                    $currentCountryTariff = null;
                    foreach ($userSmsTariffs as $userSmsTariff) {
                        if ($userSmsTariff->country_id == $phonenumber['tariff']['country']['_id']) {
                            $currentCountryTariff = $userSmsTariff;
                        }
                    }
                    if ($currentCountryTariff) {
                        $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                    }
                }

                if ($campaign->type == 'VOICE_MESSAGE') {
                    $userCan = $userRepo->canUseGift($phonenumber, $user, $maxGiftCost, $cost);
                }

                if ($userCan) {
                    $maxGiftCost += $cost;
                } else {
                    if (!$user->bonus_criteria || $user->bonus_criteria > $phonenumber['tariff']['best_margin']) {
                        $userCanAll = false;
                    }
                    if ($campaign->type == 'VOICE_MESSAGE') {
                        $maxCost += $cost;
                    }
                    if ($campaign->type == 'SMS') {
                        $maxCost += $smsCost;
                    }
                    if ($campaign->type == 'VOICE_WITH_SMS') {
                        $maxCost += $smsCost + $cost;
                    }
                }

                $tariff = $phonenumber['tariff'];
                $finalNumber = $phonenumber['phonenumber'];

                $contactObject = $user->addressBookContacts()->where('phone_number', $finalNumber)
                    ->first();

                if (!$contactObject) {
                    $contactData = [
                        'phone_number' => $finalNumber,
                        'tariff_id' => $tariff['_id'],
                        'type' => $campaignDbRepo->checkIsPhoneNumberMobile($finalNumber),
                        'user_id' => $user->_id,
                    ];
                    try {
                        $contactObject = \App\Models\AddressBookContact::create($contactData);
                    } catch (\Exception $e) {
                        $contactObject = null;
                    }
                }
                if ($contactObject) {
                    $contactObject->groups()->attach($groupObject->_id);
                }

                $isUserNumberFromEu = isset($usersCallerId->tariff->country) ? $usersCallerId->tariff->country->is_eu_member : false;
                $isNumberFromEu = $tariff->country->is_eu_member;
                $isFromNotEuToEu = !$isUserNumberFromEu && $isNumberFromEu;
                $isFree = $this->shouldPhonenumberBeFree($tariff, $usersCallerId, $campaign, $user);

                $phonenumbersToadd[] = [
                    'campaign_id' => $campaign->_id,
                    'action_type' => $campaign->type,
                    'phone_no' => $finalNumber,
                    'should_put_three_asterisks' => $contactObject->should_put_three_asterisks,
                    'created_at' => date('Y-m-d H:i:s'),
                    'retries' => 0,
                    'tariff_id' => $tariff->_id,
                    'user_id' => $user->_id,
                    'type' => $campaignDbRepo->checkIsPhoneNumberMobile($finalNumber),
                    'is_from_not_eu_to_eu' => $isFromNotEuToEu,
                    'is_free' => $isFree,
                ];
            }

            if ($shouldUseAllContacts) {
                $recipentsCounter = 0;
                $contacts = $user->addressBookContacts();
                $contacts = $contacts->selectRaw('count(*) as count, tariff_id, user_id, tariffs.prefix, tariffs.country_id, tariffs.best_margin, countries.customer_price')
                    ->groupBy('tariff_id')
                    ->leftJoin('tariffs', 'address_book_contacts.tariff_id', '=', 'tariffs._id')
                    ->leftJoin('countries', 'tariffs.country_id', '=', 'countries._id')
                    ->get();
                $minimumMargin = $user->bonus_criteria;
                $availableGiftBalance = $user->bonus;
                $maxCost = 0;
                $maxGiftCost = 0;
                $userCanAll = true;
                foreach ($contacts as $contact) {

                    $smsCost = $contact->sms_customer_price * ceil(strlen($smsText) / 160);

                    if ($userSmsTariffs->count()) {
                        $currentCountryTariff = null;
                        foreach ($userSmsTariffs as $userSmsTariff) {
                            if ($userSmsTariff->country_id == $contact->_id) {
                                $currentCountryTariff = $userSmsTariff;
                            }
                        }
                        if ($currentCountryTariff) {
                            $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                        }
                    }

                    if ($recipentsCount > $recipentsCounter || is_null($recipentsCount)) {

                        if (is_null($recipentsCount)) {
                            if ($campaign->type == 'VOICE_MESSAGE') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                            }
                            if ($campaign->type == 'VOICE_WITH_SMS') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                $tempCost += $smsCost;
                            }
                            if ($campaign->type == 'SMS') {
                                $tempCost = $smsCost;
                            }
                        } else {
                            if ($contact->count + $recipentsCounter < $recipentsCount) {
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            } else {
                                $count = $recipentsCount - $recipentsCounter;
                                $tempCost = $contact->customer_price * $length * $count / 60;
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            }
                            $recipentsCounter += $contact->count;
                        }

                        if ($contact->best_margin > $minimumMargin && $availableGiftBalance > 0 && $campaign->type == 'VOICE_MESSAGE') {
                            $leftAmount = $availableGiftBalance - $tempCost;
                            if ($leftAmount < 0) {
                                $maxGiftCost += $availableGiftBalance;
                                $availableGiftBalance = 0;
                                $maxCost += abs($leftAmount);
                            } else {
                                $maxGiftCost += $tempCost;
                                $availableGiftBalance -= $tempCost;
                            }
                        } else {
                            if ($contact->best_margin > $minimumMargin) {
                                $userCanAll = false;
                            }
                            $maxCost += $tempCost;
                        }
                    } else {
                        break;
                    }
                }
            } elseif ($groupIds) {
                $recipentsCounter = 0;
                $campaign->groups()->sync($groupIds);
                $contacts = $user->addressBookContacts();
                $contacts = $contacts->whereHas('groups', function ($query) use ($groupIds) {
                    $query->whereIn('address_book_groups._id', $groupIds);
                }, '>', 0);
                $contacts = $contacts->selectRaw('count(*) as count, tariff_id, user_id, tariffs.prefix, tariffs.country_id, tariffs.best_margin, countries.customer_price')
                    ->groupBy('tariff_id')
                    ->leftJoin('tariffs', 'address_book_contacts.tariff_id', '=', 'tariffs._id')
                    ->leftJoin('countries', 'tariffs.country_id', '=', 'countries._id')
                    ->get();
                $minimumMargin = $user->bonus_criteria;
                $availableGiftBalance = $user->bonus;
                $maxCost = 0;
                $maxGiftCost = 0;
                $userCanAll = true;

                foreach ($contacts as $contact) {

                    $smsCost = $contact->sms_customer_price * ceil(strlen($smsText) / 160);
                    if ($userSmsTariffs->count()) {
                        $currentCountryTariff = null;
                        foreach ($userSmsTariffs as $userSmsTariff) {
                            if ($userSmsTariff->country_id == $contact->_id) {
                                $currentCountryTariff = $userSmsTariff;
                            }
                        }
                        if ($currentCountryTariff) {
                            $smsCost = $currentCountryTariff->cost * ceil(strlen($smsText) / 160);
                        }
                    }

                    if ($recipentsCount > $recipentsCounter || is_null($recipentsCount)) {
                        if (is_null($recipentsCount)) {
                            if ($campaign->type == 'VOICE_MESSAGE') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                            }
                            if ($campaign->type == 'VOICE_WITH_SMS') {
                                $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                $tempCost += $smsCost;
                            }
                            if ($campaign->type == 'SMS') {
                                $tempCost = $smsCost;
                            }
                        } else {
                            if ($contact->count + $recipentsCounter < $recipentsCount) {
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $contact->count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            } else {
                                $count = $recipentsCount - $recipentsCounter;
                                if ($campaign->type == 'VOICE_MESSAGE') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                }
                                if ($campaign->type == 'VOICE_WITH_SMS') {
                                    $tempCost = $contact->customer_price * $length * $count / 60;
                                    $tempCost += $smsCost;
                                }
                                if ($campaign->type == 'SMS') {
                                    $tempCost = $smsCost;
                                }
                            }
                            $recipentsCounter += $contact->count;
                        }
                        if ($contact->best_margin > $minimumMargin && $availableGiftBalance > 0 && $campaign->type == 'VOICE_MESSAGE') {
                            $leftAmount = $availableGiftBalance - $tempCost;
                            if ($leftAmount < 0) {
                                $maxGiftCost += $availableGiftBalance;
                                $availableGiftBalance = 0;
                                $maxCost += abs($leftAmount);
                            } else {
                                $maxGiftCost += $tempCost;
                                $availableGiftBalance -= $tempCost;
                            }
                        } else {
                            if ($contact->best_margin > $minimumMargin) {
                                $userCanAll = false;
                            }
                            $maxCost += $tempCost;
                        }
                    } else {
                        break;
                    }
                }
            } else {
                \App\Models\Phonenumber::insert($phonenumbersToadd);
                $campaign->is_first_run = 0;
            }

            if ($maxCost > 0 && !$userCanAll) {
                $maxCost += $maxGiftCost;
                $maxGiftCost = 0;
            }

            $campaign->retained_balance = $maxCost;
            $campaign->retained_gift_balance = $maxGiftCost;
            $campaign->is_gift_being_used = ($maxGiftCost > 0) ? true : false;
        }

        if ($campaign->status == 'stop') {
            if (count($schedulationsToAdd)) {
                $campaign->status = 'scheduled';
            } else {
                $campaign->status = 'start';
            }
        }

        $customerTimezone = $user->timezone ? $user->timezone : 'UTC';
        $serverTimezine = config('app.timezone');

        if (count($schedulationsToAdd) > 0) {
            \App\Models\Schedulation::insert($schedulationsToAdd);
            $schedulationDateFormat = max(array_map(function ($item) {
                return $item['scheduled_date']->format('Y-m-d H:i:s');
            }, $schedulationsToAdd));
            $campaign->first_scheduled_date = Carbon::createFromFormat('Y-m-d H:i:s', $schedulationDateFormat, $customerTimezone)->setTimezone($serverTimezine)->format('Y-m-d H:i:s');
        }
        $campaign->save();

        $campaign = Campaign::with(['schedulations', 'groups', 'phonenumbers'])->find($campaign->_id);

        $this->createCallingInterval($campaign);

        // Messages Log

        $type = 'INFO';
        $text = 'User with #' . $user->_id . ' id has updated campaign #' . $campaign->_id;
        $status = 'CAMPAIGN_EDITED';

        $messageLogsService->createMessageLogForUpdate($type, $text, $status, $campaign, $oldCampaign);

        // ActivityLog

        $logData = [
            'campaign_id' => $campaign->_id,
            'user_id' => $user->_id,
            'device' => 'WEBSITE',
            'action' => 'MESSAGES',
            'description' => 'User updated campaign',
        ];
        $this->activityLogRepo->createActivityLog($logData);

        $response = $this->createBasicResponse(0, 'Campaign__Updated_1');

        return response()->json(['resource' => $response]);
    } //REFACTOR UPTO HERE
    

    /**
     * Update campaign name
     * POST /campaigns/update-campaign-name
     *
     * @param Request $request
     * @return JSON
     */
    public function postUpdateCampaignName(Request $request)
    {

        $user = Auth::user();
        $id = $request->get('campaign_id');
        $campaignName = $request->get('campaign_name');
        $campaign = $user->campaigns()->where('_id', $id)->first();
        if (!$campaign) {
            $response = $this->createBasicResponse(-13, 'Campaign__does__not_exist');
            return response()->json(['resource' => $response]);
        }
        $campaign->campaign_name = $campaignName;
        $campaign->save();
        $response = $this->createBasicResponse(0, 'campaign_has_been_successfully__updated');
        return response()->json(['resource' => $response]);
    }

    /**
     * Update campaign name
     * POST /campaigns/update-campaign-name
     *
     * @param Request $request
     * @return JSON
     */
    public function postUpdateCampaignStatus(Request $request, MessageLogsService $messageLogsService)
    {
        $user = Auth::user();
        $campaignId = $request->get('campaign_id');
        $status = $request->get('status');
        $campaign = $user->campaigns()->with('phonenumbers')->whereIn('status', [
            'start', 'stop', 'scheduled', 'schedulation_in_progress', 'schedulation_idle', 'stopped_low_balance'
        ])->where('_id', $campaignId)->first();

        if (!$campaign) {
            $response = $this->createBasicResponse(-13, 'Campaign__does__not_exist');
            return response()->json(['resource' => $response]);
        }

        $maxCost = $campaign->retained_balance;
        $maxGiftCost = $campaign->retained_gift_balance;
        if ($status == 'start' && !MessageValidationService::haveEnoughBalanceForCall($status, $maxCost, $maxGiftCost, $user)) {
            $response = $this->createBasicResponse(-12, 'balance_is_not_enough_for_creating_message');
            return response()->json(['resource' => $response]);
        }

        if ($status == 'start') {
            if ($campaign->schedulation_original_data) {
                $campaign->status = 'scheduled';
            } else {
                $campaign->status = $status;
            }

            $campaign->save();
            $campaign->phonenumbers()
                ->where('status', 'FAILED')
                ->where('retries', '<', 3)
                ->chunk(2000, function ($phoneNumbers) {
                    $phoneNumbersIds = $phoneNumbers->toArray();
                    Phonenumber::whereIn('_id', $phoneNumbersIds)
                        ->update(['status' => 'IN_PROGRESS']);
                });

        } else {
            $campaign->status = $status;
        }

        if ($status == 'stop') {
            $campaign->first_scheduled_date = null;
            $campaign->save();

            $campaign->phonenumbers()
                ->where('status', 'IN_PROGRESS')
                ->chunk(2000, function ($phoneNumbers) {
                    $phoneNumbersIds = $phoneNumbers->toArray();
                    Phonenumber::whereIn('_id', $phoneNumbersIds)
                        ->update(['status' => 'FAILED']);
                });

            $text = 'User with #' . $user->_id . ' id has stopped #' . $campaign->_id . ' campaign';
            $messageLogsService->createMessageLogForStatus('INFO', $text, 'CAMPAIGN_STOPPED', $campaign);

        }

        if ($status == 'dialing_completed') {

            $campaign->retained_balance = null;
            $campaign->retained_gift_balance = null;
            $campaign->save();
            $text = 'User with #' . $user->_id . ' id has marked as finished #' . $campaign->_id . ' campaign';
            $messageLogsService->createMessageLogForStatus('INFO', $text, 'CAMPAIGN_MARKED_AS_FINISHED', $campaign);

            $campaign->phonenumbers()->whereIn('status', ['IN_PROGRESS', 'IDLE'])->update([
                'status' => 'CANCELLED',
            ]);
        }


        $response = $this->createBasicResponse(0, 'campaign_has_been_successfully_updated');

        return response()->json(['resource' => $response]);
    }

    /**
     * Send request to API for removing campaign.
     * DELETE /campaigns/remove-campaign/{id}
     *
     * @param integer $id
     * @return JSON
     */
    public function deleteRemoveCampaign($id, MessageLogsService $messageLogsService)
    {
        $user = Auth::user();
        $campaign = \App\Models\Campaign::where('user_id', $user->_id)
            ->where('_id', $id)
            ->where(function ($query) {
                $query->where('status', 'saved')
                    ->orWhere(function ($newQuery) {
                        $newQuery->where('status', 'scheduled')
                            ->whereHas('schedulations', function ($schedQuery) {
                                $schedQuery->where('is_finished', 1);
                            }, '=', 0);
                    });
            })->first();

        if ($campaign->status == 'scheduled' and count($campaign->callsCount)) {
            $response = $this->createBasicResponse(-2, 'forbidden');
            return response()->json(['resource' => $response]);
        }

        if (!$campaign) {
            $response = $this->createBasicResponse(-1, 'campaign_does_not_exists_or_not_belongs_to_you.');
            return response()->json(['resource' => $response]);
        }
        $cacheService = new UserDataRedisCacheService();
        $cacheService->incrementMessages($campaign->user_id, -1);
        $logData = [
            'campaign_id' => $campaign->_id,
            'user_id' => $user->_id,
            'device' => 'WEBSITE',
            'action' => 'MESSAGES',
            'description' => 'User remoevd campaign',
        ];
        $this->activityLogRepo->createActivityLog($logData);

        //$campaign->batches()->delete();
        //$campaign->batchRepeats()->delete();
        $campaign->prototype()->delete();
        $campaign->delete();

        $type = 'INFO';
        $text = 'User with #' . $user->_id . ' id has removed #' . $id . ' campaign';
        $status = 'CAMPAIGN_REMOVED';

        $messageLogsService->createMessageLogForRemove($type, $text, $status, $campaign);

        $response = $this->createBasicResponse(0, 'campaign_removed');
        return response()->json(['resource' => $response]);
    }

    /**
     * Upload file for showing in the page , and return file name .
     * POST /campaigns/upload-file
     *
     * @param Request $request
     * @return JSON
     */
    public function postUploadFile(Request $request)
    {
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $name = str_random(10) . '.' . $extension;
        $path = public_path() . '/uploads/';
        $file->move($path, $name);
        return response()->json(['resource' => $name]);
    }

    /**
     * Upload audio file to server.
     * POST /campaigns/upload-audio-file
     *
     * @param Request $request
     * @return JSON
     */
    public function postUploadAudioFile(Request $request, FileService $fileRepo)
    {
        $user = Auth::user();
        $voiceFile = $request->file('file');
        $originalName = $voiceFile->getClientOriginalName();
        $extension = $voiceFile->getClientOriginalExtension();
        $isTemplate = $request->get('is_template');
        $validExtensions = ['mp3', 'wav', 'm4a'];
        if (!in_array($extension, $validExtensions)) {
            $response = [
                'error' => [
                    'no' => -12,
                    'text' => 'file_format_not_supported',
                ],
            ];
            return response()->json(['resource' => $response]);
        }

        $uploadFolder = public_path() . '/uploads/audio/';
        $newName = str_random();
        $originalName = $voiceFile->getClientOriginalName();
        $fileExtension = $voiceFile->getClientOriginalExtension();
        $voiceFile->move($uploadFolder, $newName . '.' . $fileExtension);

        $isConvertedFromM4a = false;
        // if($extension == 'm4a'){
        //            $isConvertedFromM4a = true;
        //            $cmd = 'ffmpeg -i ' . $uploadFolder . $newName . '.' . $extension . ' ' . $uploadFolder . $newName . '.wav';
        //            $response = shell_exec( $cmd );
        //            $extension = 'wav';
        //        }

        if ($extension != 'gsm') {
            $gsmAudioFile = $newName . '.gsm';

            $cmd = 'sox ' . $uploadFolder . $newName . '.' . $extension . ' -r 8000 -c 1 ' . $uploadFolder . $gsmAudioFile . ' silence 1 0.1 1%';
            $response = shell_exec($cmd);
            $audioFilename = Helper::_extractFileName($gsmAudioFile);
        } else {
            $gsmAudioFile = Helper::_extractFileName($newName) . '.gsm';
        }
        if ($isConvertedFromM4a) {
            //unlink($uploadFolder . $newName . '.' . $extension);
        }

        $file = $fileRepo->createFile([
            'orig_filename' => preg_replace('/\.[^.\s]{3,4}$/', '', $originalName) . '.' . $extension,
            'map_filename' => $newName . '.' . $extension,
            'extension' => $extension,
            'stripped_name' => $newName,
            'user_id' => $user->_id,
        ]);

        $length = $fileRepo->getFileSizeByPK($file->_id);
        $file->length = floor($length / 1000);
        $file->type = 'UPLOADED';
        $file->is_template = $isTemplate;
        $file->save();

        $fileRepo->moveAudioFileToAmazon($file->map_filename);
        $fileRepo->moveAudioFileToAmazon($file->stripped_name . '.gsm');
        \File::delete($uploadFolder . $newName . "." . $fileExtension);
        if ($isTemplate) {
            $cacheService = new UserDataRedisCacheService();
            $cacheService->incrementMessages($user->_id, 1);
        }

        $file['amazon_s3_url'] = $this->getAmazonS3Url($file->map_filename);
        $response = [
            'error' => [
                'no' => 0,
                'message' => 'file_created',
            ],
            'file' => $file,
        ];
        return response()->json(['resource' => $response]);
    }

    /**
     * Send request to server for creating audio file from text.
     * POST /campaigns/create-audio-from-text
     *
     * @param Request $request
     * @return JSON
     */
    public function postCreateAudioFromText(Request $request, FileService $fileRepo)
    {
        $user = Auth::user();
        $text = $request->get('text');
        $savedFrom = $request->get('saved_from');

        // $pattern = '/\d{4,}/';
        // preg_match_all($pattern, $text, $matches);
        // $replacement = $patterns = [];
        // foreach (current($matches) as $key => $row) {
        //     $patterns[$key] = "/{$row}/";
        //     $replacement[$key] = implode('-', str_split($row));
        // }
        // $text = preg_replace($patterns, $replacement, $text);

        $language = $request->get('language');
        $fileResponse = $fileRepo->createFromText($text, $language, $user->_id, $savedFrom);
        if (!$fileResponse->file) {
            $response = $this->createBasicResponse(-1, $fileResponse->error);
            return response()->json(['resource' => $response]);
        }
        $file = $fileResponse->file;

        $file['amazon_s3_url'] = $this->getAmazonS3Url($file->map_filename);

        $logData = [
            'user_id' => $user->_id,
            'device' => 'WEBSITE',
            'action' => 'MESSAGES',
            'description' => 'User used tts service',
        ];
        $this->activityLogRepo->createActivityLog($logData);

        $response = [
            'error' => [
                'no' => 0,
                'message' => 'file_created',
            ],
            'file' => $file,
        ];
        return response()->json(['resource' => $response]);
    }

    /**
     * Check if the call should be free .
     * If the call is going to be free increment users daily
     * used free calls .
     *
     * @param Tariff $tariff
     * @param CallerId $callerId
     * @param Campaign $campaign
     * @param User $user
     * @return bool
     */
    private function shouldPhonenumberBeFree($tariff, $usersCallerId, $campaign, $user)
    {
        $country = $usersCallerId->tariff->country;
        if ($tariff->country_id != $usersCallerId->tariff->country_id) {
            return false;
        }
        if (!$user->country) {
            return false;
        }

        if ($tariff->best_margin < $country->free_call_minimum_margin) {
            return false;
        }

        $freeMessagesPerDay = $country->web_free_messages_count_per_day;
        $freeMessageMaxDuration = $country->web_free_message_max_duration;
        if ($campaign->transfer_digit ||
            $campaign->callback_digit ||
            $campaign->replay_digit ||
            $campaign->do_not_call_digit ||
            $campaign->playback_count > 0 ||
            $campaign->voiceFile->length > $freeMessageMaxDuration
        ) {
            return false;
        }

        if (!$user->last_used_free_credit_at || !Carbon::createFromFormat('Y-m-d H:i:s', $user->last_used_free_credit_at)->isToday()) {
            $user->free_calls_made = 0;
        }
        if ($user->free_calls_made >= $freeMessagesPerDay) {
            return false;
        }
        $user->last_used_free_credit_at = Carbon::now();
        $user->free_calls_made++;
        $user->save();
        return true;
    }
}
