<?php

namespace App\Services\Webhook\Drivers;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyMeWebHookDriver implements WebhookInterface
{

    public function name(): string
    {
        return 'verify.me';
    }

    public function validate(Request $request, array $data, string $raw): bool
    {
        try {
            return hash_equals(
                hash_hmac(
                    'sha512',
                    $raw,
                    config('services.verifyme.secret.test'),
                ),
                $request->header('x-verifyme-signature')
            );
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function process(Request $request, array $data, string $raw): Response
    {


        $eventType = $request->all()['type'];
        if (
            $this->eventTypeIsAddress($eventType) || $this->eventTypeIsGaurantor($eventType) ||
            $this->eventTypeIsEmployment($eventType) || $this->eventTypeIsProperty($eventType)
        ) {

            if ($this->eventTypeIsAddress($eventType)) {
                $userPhoneNumber = $request->all()['data']['applicant']['phone'];

                $userInfo = $this->getUser('phone', $userPhoneNumber);
                if (!$userInfo) return $this->sendError(
                    'User is not authenticated',
                    Response::HTTP_UNAUTHORIZED
                );

                return $this->sendSuccess([
                    $request->all()['data'],
                    'user' => $userInfo
                ], Response::HTTP_OK);
            }

            if ($this->eventTypeIsEmployment($eventType)) {
                $applicant = $request->all('data')['applicant'];
                $email = $applicant['email'];

                $userInfo = $this->getUser('email', $email);
                if (!$userInfo) return $this->sendError("User is not authenticated");

                return $this->sendSuccess([
                    $request->all()['data'],
                    $userInfo
                ]);
            }

            if ($this->eventTypeIsProperty($eventType)) {
                $personalContactInformation = $request->all()['data']['propertyContactPerson'];
                $email = $personalContactInformation['email'];

                $userInfo = $this->getUser('email', $email);

                return $this->sendSuccess([
                    $request->all()['data'],
                    $userInfo
                ]);
            }

            if ($this->eventTypeIsEmployment($eventType)) {
                $applicant = $request->all('data')['applicant'];
                $email = $applicant['email'];

                $userInfo = $this->getUser('email', $email);
                if ($userInfo) return $this->sendError('user unauthenticated');

                return $this->sendSuccess([
                    $request->all()['data'],
                    $userInfo
                ]);
            }

            if ($this->eventTypeIsGaurantor($eventType)) {
                $applicant = $request->all('data')['applicant'];
                $email = $applicant['email'];

                $userInfo = $this->getUser('email', $email);
                if ($userInfo) return $this->sendError('User unauthorized');


                return $this->sendSuccess([
                    $request->all()['data'],
                    $userInfo
                ]);
            }
        }

        return $this->sendError('Unrecognized verification');
    }

    private function eventTypeIsGaurantor(string $eventType): bool
    {
        return $eventType == "guarantor";
    }

    private function eventTypeIsAddress(string $eventType): bool
    {
        return $eventType == "address";
    }

    private function eventTypeIsEmployment(string $eventType): bool
    {
        return $eventType == "employment";
    }

    private function eventTypeIsProperty(string $eventType): bool
    {
        return $eventType == "property";
    }

    private function sendSuccess($data = [], $code = Response::HTTP_OK): Response
    {
        return response()->json([
            'status' => 'success',
            'data' => $data
        ], $code);
    }

    private function getUser(string $searchBy, string $value = 'email')
    {
        return User::where($searchBy, $value)->first();
    }

    private function sendError($message = '', $code = Response::HTTP_UNAUTHORIZED): Response
    {
        return response()->json([
            'status' => 'failed',
            'message' => $message,
        ], $code);
    }
}
