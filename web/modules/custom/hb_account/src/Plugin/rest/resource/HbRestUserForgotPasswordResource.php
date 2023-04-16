<?php

namespace Drupal\hb_account\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Represents User Forgot Password records as resources.
 *
 * @RestResource (
 *   id = "hb_rest_user_forgot_password",
 *   label = @Translation("User Forgot Password"),
 *   uri_paths = {
 *     "create" = "/api/user/forgot-password"
 *   }
 * )
 *
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
class HbRestUserForgotPasswordResource extends ResourceBase {

	const FORGOT_PASSWORD_TEMPLATE = 'email_template';
	const FIELD_BODY = 'field_m_e_body';
	const FIELD_SUBJECT = 'field_m_e_subject';
	const OTP_EXPIRED_TIME = 5;
	const OTP_RESEND_TIME = 1;
	/**
	 * Responds to POST requests and saves the new record.
	 *
	 * @param array $data
	 *   Data to write into the database.
	 *
	 * @return JsonResponse
	 *   The HTTP response object.
	 */
	public function post(array $data): JsonResponse {
		if (\Drupal::service('hb_guard.data_guard')->guardRequiredData([
			'mail',
		], $data)) {
			$userData = \Drupal::service('user.data');
			return new JsonResponse(['message' => $userData->get('hb_guard', \Drupal::currentUser()->id(), 'guard_field') . ' is missing!'], 400);
		}

		$tempstore = \Drupal::service('tempstore.private');
		$store = $tempstore->get('hb_account');
		$exist_otp = $store->get('forgot_password.otp.' . $data['mail']);

		// Verify OTP
		if (isset($data['hash']) and isset($data['otp'])) {
			if (!$exist_otp) {
				return new JsonResponse(['message' => 'Forbidden!'], 403);
			}
			$otp_values = json_decode($exist_otp);
			if ($data['otp'] != $otp_values->otp) {
				return new JsonResponse(['message' => 'Invalid otp!'], 410);
			}
			if (strtotime(date('Y-m-d H:i:s', strtotime(
						date('Y-m-d H:i:s',
							$otp_values->created) . '+'. self::OTP_EXPIRED_TIME .' minutes')
				)) > \Drupal::time()->getRequestTime()) {
				return new JsonResponse([
					'message' => 'Success!',
					'results' => $otp_values->hash,
				], 410);
			}
			return new JsonResponse(([
				'message' => 'OTP is expired!',
			]), 410);
		}

		// Verify OTP Success and go to Reset password
		if (isset($data['hash']) and isset($data['pass'])) {
			if (!$exist_otp) {
				return new JsonResponse(['message' => 'Forbidden!'], 403);
			}
			$otp_values = json_decode($exist_otp);
			if ($data['hash'] != $otp_values->hash) {
				return new JsonResponse(['message' => 'Invalid hash!'], 410);
			}
			try {
				$user = user_load_by_mail($otp_values->mail);
				$user->setPassword($data['pass']);
				$user->save();
				$store->delete('forgot_password.otp.' . $data['mail']);
				$this->logger->notice('User @user has changed password.', ['@user' => $user->label()]);
				return new JsonResponse(['message' => 'Success'], 200);
			} catch (\Exception $e) {
				$this->logger->error($e);
				return new JsonResponse(['message' => $e->getMessage()], 500);
			}
		}

		// Send OTP or Resend OTP
		try {
			$exist_user = \Drupal::entityTypeManager()
				->getStorage('user')
				->loadByProperties([
					'mail' => $data['mail'],
				]);
			if (!$exist_user) {
				return new JsonResponse(['message' => 'User doesn\'t exist!'], 404);
			}

		} catch (\Exception $e) {
			$this->logger->error($e);
			return new JsonResponse([], 500);
		}
		$otp = $this->generateOTP();
		$hash = \Drupal::service('hb_guard.data_guard')->generateHash();
		$exist_otp = $store->get('forgot_password.otp.' . $data['mail']);

		if (!$exist_otp) {
			$store->set('forgot_password.otp.' . $data['mail'], json_encode([
				'otp' => $otp,
				'mail' => $data['mail'],
				'hash' => $hash,
				'created' => \Drupal::time()->getRequestTime(),
			]));
		}

		if ($exist_otp) {
			$otp_values = json_decode($exist_otp);
			if (strtotime(date('Y-m-d H:i:s', strtotime(
				date('Y-m-d H:i:s',
					$otp_values->created) . '+'. self::OTP_RESEND_TIME .' minutes')
				)) > \Drupal::time()->getRequestTime()) {
				$store->set('forgot_password.otp.' . $data['mail'], json_encode([
					'otp' => $otp_values->otp,
					'mail' => $otp_values->mail,
					'hash' => $hash,
					'created' => $otp_values->created,
				]));
				return new JsonResponse([
					'message' => 'Success!',
					'results' => $hash,
				], 200);
			}
			$store->set('forgot_password.otp.' . $data['mail'], json_encode([
				'otp' => $otp,
				'mail' => $data['mail'],
				'hash' => $hash,
				'created' => \Drupal::time()->getRequestTime(),
			]));
		}

		$config = \Drupal::config('system.site');
		$base_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('hb_message', self::FORGOT_PASSWORD_TEMPLATE);
		$subject_default_value = reset($base_fields[self::FIELD_SUBJECT]->get('default_value'))['value'];
		$field_subject_value = t($subject_default_value, [
			':[site:url]' => \Drupal::request()->getHost(),
		]);

		$body_default_value = reset($base_fields[self::FIELD_BODY]->get('default_value'))['value'];
		$field_body_value = t($body_default_value, [
			':[site:name]' => $config->get('name'),
			':[site:url]' => \Drupal::request()->getHost(),
			':[site:mail]' => $config->get('mail'),
			':[otp]' => $otp,
			':[mail]' => $data['mail'],
		]);

		$values = [
			'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
			'params' => [
				self::FIELD_SUBJECT => $field_subject_value,
				self::FIELD_BODY    => $field_body_value,
			],
		];

		\Drupal::service('hb_message.send')->send($data['mail'], self::FORGOT_PASSWORD_TEMPLATE, $values);
		return new JsonResponse([
			'message' => 'Send OTP Success!',
			'results' => $hash,
		], 200);
	}

	private function generateOTP() {
		return rand(100000, 999999);
	}


}