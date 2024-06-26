<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription\Captcha\Validator;

interface CaptchaValidator {
  /**
   * @param array $data
   * @return bool
   * @throws ValidationError
   */
  public function validate(array $data): bool;
}
