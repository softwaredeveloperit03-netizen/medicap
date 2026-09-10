import { Directive, ElementRef, HostBinding, HostListener, Injector, Input } from '@angular/core';
import { AbstractControl, NG_VALIDATORS, NgControl, ValidationErrors, Validator } from '@angular/forms';
import {
  CANADIAN_PHONE_PATTERN_HINT,
  canadianPhoneValidator,
  formatCanadianPhone,
  stripToCanadianPhoneDigits,
} from './validators/canadian-phone.validator';

/** Form control / input name fragments that hold phone numbers (not person names). */
const PHONE_CONTROL_SUFFIX =
  /(contact_no|contact_number|contact_number2|contactNumber|contactno|contact_phone|contact_phone_no|mobile_no|mobile_no1|mobile_no2|mobile|mob_no|mobNo|phone_no|phone|telephone|driver_contact|driver_mobile|c_mobile_no|whatsapp|permanent_mobile|permanent_telephone|temp_mobile|temp_telephone|emp_contact|cr_mobile_no)$/i;

/**
 * Validates phone / mobile / contact / telephone fields as Canadian NANP numbers.
 * Attach explicitly with `canadianPhone`, or rely on auto-matching name/type selectors.
 */
@Directive({
  selector: `
    input[canadianPhone],
    input[name="contact_no"],
    input[name="contact_number"],
    input[name="contactNumber"],
    input[name="contactno"],
    input[name="contact_phone_no"],
    input[name="phoneNumber"],
    input[name="phone_no"],
    input[name="phone"],
    input[name="mobile_no"],
    input[name="mobile_no1"],
    input[name="mobile_no2"],
    input[name="cr_mobile_no"],
    input[name="mobile"],
    input[name="mobNo"],
    input[name="personMobNo"],
    input[name="driver_contact"],
    input[name="driver_mobile"],
    input[name="c_mobile_no"],
    input[name="whatsappNO"],
    input[name="telephone_no"],
    input[name="permanent_mobile_no"],
    input[name="permanent_contact"],
    input[name="permanent_telephone"],
    input[name="temp_mobile"],
    input[name="temp_contact"],
    input[name="temp_telephone"],
    input[name="cPersonMob"],
    input[name="emp_contact"],
    input[formcontrolname="contact_number"],
    input[formcontrolname="c_mobile_no"],
    input[formcontrolname="mobile"],
    input[formcontrolname="phone_no"],
    input[formcontrolname="contact_no"],
    input[formcontrolname="mobNo"],
    input[formcontrolname="mobile_no"]
  `,
  providers: [
    {
      provide: NG_VALIDATORS,
      useExisting: CanadianPhoneDirective,
      multi: true,
    },
  ],
})
export class CanadianPhoneDirective implements Validator {
  @Input() canadianPhone: boolean | string = true;

  @HostBinding('attr.maxlength') maxlength = '17';

  /** Optional; only applied when explicitly set — never auto-fill OTP/PIN boxes. */
  @Input() phonePlaceholder = '';

  constructor(
    private el: ElementRef<HTMLInputElement>,
    private injector: Injector
  ) {
    const node = el.nativeElement;
    const maxLen = node.getAttribute('maxlength');
    const isPinBox =
      (maxLen !== null && Number(maxLen) > 0 && Number(maxLen) <= 2) ||
      /\b(pin-box|reauth-pin|login-pin)\b/i.test(node.className || '') ||
      /-pin-/i.test(node.id || '');
    if (isPinBox) {
      this.canadianPhone = false;
      this.maxlength = maxLen || '1';
      node.removeAttribute('placeholder');
      return;
    }
    // Do not inject watermarks by default; callers that want one pass [phonePlaceholder].
    if (this.phonePlaceholder && !node.getAttribute('placeholder')) {
      node.setAttribute('placeholder', this.phonePlaceholder);
    }
  }

  /** Lazy lookup avoids NG0200 (directive provides NG_VALIDATORS and must not inject NgControl in constructor). */
  private getControl(): AbstractControl | null {
    return this.injector.get(NgControl, null)?.control ?? null;
  }

  validate(control: AbstractControl): ValidationErrors | null {
    if (this.canadianPhone === false || this.canadianPhone === 'false') {
      return null;
    }
    return canadianPhoneValidator(control);
  }

  @HostListener('blur')
  onBlur(): void {
    if (this.canadianPhone === false || this.canadianPhone === 'false') {
      return;
    }
    const control = this.getControl();
    if (!control || control.value == null || String(control.value).trim() === '') {
      return;
    }
    const formatted = formatCanadianPhone(control.value);
    if (formatted && stripToCanadianPhoneDigits(control.value).length === 10) {
      control.setValue(formatted, { emitEvent: false });
    }
  }

  @HostListener('focus')
  onFocus(): void {
    if (this.canadianPhone === false || this.canadianPhone === 'false') {
      return;
    }
    const control = this.getControl();
    if (!control?.value) {
      return;
    }
    const el = this.el.nativeElement;
    const name = (el.getAttribute('name') || el.getAttribute('formcontrolname') || '').trim();
    if (name && !PHONE_CONTROL_SUFFIX.test(name)) {
      return;
    }
    const digits = stripToCanadianPhoneDigits(control.value);
    if (digits.length === 10 && String(control.value).trim() !== digits) {
      control.setValue(digits, { emitEvent: false });
    }
  }

  @HostListener('input')
  onInput(): void {
    const el = this.el.nativeElement;
    if (el.getAttribute('title') === CANADIAN_PHONE_PATTERN_HINT) {
      return;
    }
    el.setAttribute('title', CANADIAN_PHONE_PATTERN_HINT);
  }
}
