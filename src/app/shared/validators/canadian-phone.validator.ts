import { AbstractControl, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';

/** NANP 10-digit (Canada / US) after stripping optional leading +1 */
export const CANADIAN_PHONE_NANP_REGEX = /^[2-9]\d{2}[2-9]\d{6}$/;

export const CANADIAN_PHONE_PATTERN_HINT =
  'Enter a valid Canadian phone number (10 digits, e.g. 416-555-1234 or (416) 555-1234).';

/** HTML5 pattern for digit-only entry (optional +1 prefix). */
export const CANADIAN_PHONE_HTML_PATTERN = '^(\\+?1[-.\\s]?)?\\(?[2-9]\\d{2}\\)?[-.\\s]?[2-9]\\d{2}[-.\\s]?\\d{4}$';

export function stripToCanadianPhoneDigits(value: unknown): string {
  let digits = String(value ?? '').replace(/\D/g, '');
  if (digits.length === 11 && digits.startsWith('1')) {
    digits = digits.slice(1);
  }
  return digits;
}

export function isValidCanadianPhone(value: unknown): boolean {
  return CANADIAN_PHONE_NANP_REGEX.test(stripToCanadianPhoneDigits(value));
}

export function canadianPhoneValidator(control: AbstractControl): ValidationErrors | null {
  const raw = control.value;
  if (raw == null || String(raw).trim() === '') {
    return null;
  }
  return isValidCanadianPhone(raw) ? null : { canadianPhone: true };
}

/** Use on reactive form controls: required + Canadian phone. */
export function canadianPhoneRequiredValidators(): ValidatorFn[] {
  return [Validators.required, canadianPhoneValidator];
}

/** Display format: (416) 555-1234. Returns empty string for null/blank; original value if not 10-digit NANP. */
export function formatCanadianPhone(value: unknown): string {
  const raw = value == null ? '' : String(value).trim();
  if (raw === '') {
    return '';
  }
  const digits = stripToCanadianPhoneDigits(raw);
  if (digits.length === 10) {
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
  }
  return raw;
}
