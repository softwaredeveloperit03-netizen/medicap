import { AbstractControl, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';

export interface CountryPhoneRule {
  minDigits: number;
  maxDigits: number;
  /** Applied to national digits after stripping dial code / trunk prefix. */
  nationalPattern?: RegExp;
  dialCode?: string;
  example: string;
  format?: (digits: string) => string;
}

const NANP_RULE: CountryPhoneRule = {
  minDigits: 10,
  maxDigits: 10,
  nationalPattern: /^[2-9]\d{2}[2-9]\d{6}$/,
  dialCode: '1',
  example: '416-555-1234',
  format: formatNanpPhone,
};

/** Country-specific phone rules; unknown countries use DEFAULT_PHONE_RULE. */
const COUNTRY_PHONE_RULES: Record<string, CountryPhoneRule> = {
  Canada: NANP_RULE,
  'United States of America': NANP_RULE,
  India: {
    minDigits: 10,
    maxDigits: 10,
    nationalPattern: /^[6-9]\d{9}$/,
    dialCode: '91',
    example: '98765 43210',
    format: (d) => `${d.slice(0, 5)} ${d.slice(5)}`,
  },
  'United Kingdom': {
    minDigits: 10,
    maxDigits: 11,
    dialCode: '44',
    example: '020 7946 0958',
    format: (d) => (d.length === 11 ? `${d.slice(0, 4)} ${d.slice(4, 7)} ${d.slice(7)}` : `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6)}`),
  },
  Australia: {
    minDigits: 9,
    maxDigits: 10,
    dialCode: '61',
    example: '412 345 678',
    format: (d) => (d.length === 9 ? `${d.slice(0, 3)} ${d.slice(3, 6)} ${d.slice(6)}` : `${d.slice(0, 4)} ${d.slice(4, 7)} ${d.slice(7)}`),
  },
  China: {
    minDigits: 11,
    maxDigits: 11,
    nationalPattern: /^1\d{10}$/,
    dialCode: '86',
    example: '138 0013 8000',
    format: (d) => `${d.slice(0, 3)} ${d.slice(3, 7)} ${d.slice(7)}`,
  },
  Germany: { minDigits: 10, maxDigits: 11, dialCode: '49', example: '30 12345678' },
  France: { minDigits: 9, maxDigits: 10, dialCode: '33', example: '6 12 34 56 78', format: formatGroupedBy2 },
  Italy: { minDigits: 9, maxDigits: 10, dialCode: '39', example: '312 345 6789' },
  Spain: { minDigits: 9, maxDigits: 9, dialCode: '34', example: '612 34 56 78', format: formatGroupedBy2 },
  Japan: { minDigits: 10, maxDigits: 11, dialCode: '81', example: '90 1234 5678' },
  'South Korea': { minDigits: 9, maxDigits: 11, dialCode: '82', example: '10 1234 5678' },
  Brazil: { minDigits: 10, maxDigits: 11, dialCode: '55', example: '11 91234-5678' },
  Mexico: { minDigits: 10, maxDigits: 10, dialCode: '52', example: '55 1234 5678' },
  Pakistan: { minDigits: 10, maxDigits: 10, nationalPattern: /^3\d{9}$/, dialCode: '92', example: '300 1234567' },
  Bangladesh: { minDigits: 10, maxDigits: 10, nationalPattern: /^1\d{9}$/, dialCode: '880', example: '1712 345678' },
  'United Arab Emirates': { minDigits: 9, maxDigits: 9, nationalPattern: /^5\d{8}$/, dialCode: '971', example: '50 123 4567' },
  'Saudi Arabia': { minDigits: 9, maxDigits: 9, nationalPattern: /^5\d{8}$/, dialCode: '966', example: '50 123 4567' },
  Nigeria: { minDigits: 10, maxDigits: 10, dialCode: '234', example: '802 123 4567' },
  'South Africa': { minDigits: 9, maxDigits: 9, dialCode: '27', example: '82 123 4567' },
  Singapore: { minDigits: 8, maxDigits: 8, nationalPattern: /^[689]\d{7}$/, dialCode: '65', example: '8123 4567', format: (d) => `${d.slice(0, 4)} ${d.slice(4)}` },
  Malaysia: { minDigits: 9, maxDigits: 10, dialCode: '60', example: '12 345 6789' },
  Philippines: { minDigits: 10, maxDigits: 10, nationalPattern: /^9\d{9}$/, dialCode: '63', example: '917 123 4567' },
  Indonesia: { minDigits: 9, maxDigits: 12, dialCode: '62', example: '812 3456 7890' },
  Thailand: { minDigits: 9, maxDigits: 9, nationalPattern: /^[689]\d{8}$/, dialCode: '66', example: '81 234 5678' },
  Vietnam: { minDigits: 9, maxDigits: 10, dialCode: '84', example: '91 234 56 78' },
  Turkey: { minDigits: 10, maxDigits: 10, nationalPattern: /^5\d{9}$/, dialCode: '90', example: '532 123 4567' },
  Russia: { minDigits: 10, maxDigits: 10, dialCode: '7', example: '912 345-67-89' },
  Netherlands: { minDigits: 9, maxDigits: 9, dialCode: '31', example: '6 12345678' },
  Switzerland: { minDigits: 9, maxDigits: 9, dialCode: '41', example: '79 123 45 67' },
  Sweden: { minDigits: 9, maxDigits: 10, dialCode: '46', example: '70 123 45 67' },
  Norway: { minDigits: 8, maxDigits: 8, dialCode: '47', example: '412 34 567' },
  Denmark: { minDigits: 8, maxDigits: 8, dialCode: '45', example: '20 12 34 56' },
  Poland: { minDigits: 9, maxDigits: 9, dialCode: '48', example: '512 345 678' },
  Portugal: { minDigits: 9, maxDigits: 9, dialCode: '351', example: '912 345 678' },
  Ireland: { minDigits: 9, maxDigits: 9, dialCode: '353', example: '85 123 4567' },
  'New Zealand': { minDigits: 8, maxDigits: 10, dialCode: '64', example: '21 123 4567' },
  Israel: { minDigits: 9, maxDigits: 9, dialCode: '972', example: '50 123 4567' },
  Egypt: { minDigits: 10, maxDigits: 10, dialCode: '20', example: '10 1234 5678' },
  'Hong Kong': { minDigits: 8, maxDigits: 8, nationalPattern: /^[569]\d{7}$/, dialCode: '852', example: '5123 4567', format: (d) => `${d.slice(0, 4)} ${d.slice(4)}` },
  Nepal: { minDigits: 10, maxDigits: 10, nationalPattern: /^9[78]\d{8}$/, dialCode: '977', example: '984 1234567' },
  'Sri Lanka': { minDigits: 9, maxDigits: 9, nationalPattern: /^7\d{8}$/, dialCode: '94', example: '71 234 5678' },
};

function dc(dialCode: string, minDigits: number, maxDigits: number, example: string): CountryPhoneRule {
  return { minDigits, maxDigits, dialCode, example };
}

/** Remaining dropdown countries (candidate form) not covered above. */
const EXTRA_COUNTRY_PHONE_RULES: Record<string, CountryPhoneRule> = {
  Afghanistan: dc('93', 9, 9, '70 123 4567'),
  Albania: dc('355', 9, 9, '67 212 3456'),
  Algeria: dc('213', 9, 9, '551 23 45 67'),
  'American Samoa': NANP_RULE,
  Andorra: dc('376', 6, 9, '312 345'),
  Angola: dc('244', 9, 9, '923 123 456'),
  Anguilla: NANP_RULE,
  'Antigua & Barbuda': NANP_RULE,
  Argentina: dc('54', 10, 11, '9 11 1234 5678'),
  Armenia: dc('374', 8, 8, '77 123456'),
  Aruba: dc('297', 7, 7, '560 1234'),
  Austria: dc('43', 10, 13, '664 1234567'),
  Azerbaijan: dc('994', 9, 9, '40 123 45 67'),
  Bahamas: NANP_RULE,
  Bahrain: dc('973', 8, 8, '3600 1234'),
  Barbados: NANP_RULE,
  Belarus: dc('375', 9, 9, '29 123 45 67'),
  Belgium: dc('32', 8, 9, '470 12 34 56'),
  Belize: dc('501', 7, 7, '622 1234'),
  Benin: dc('229', 8, 8, '90 12 34 56'),
  Bermuda: NANP_RULE,
  Bhutan: dc('975', 8, 8, '17 12 34 56'),
  Bolivia: dc('591', 8, 8, '712 34567'),
  'Bosnia & Herzegovina': dc('387', 8, 9, '61 123 456'),
  Botswana: dc('267', 7, 8, '71 123 456'),
  Brunei: dc('673', 7, 7, '712 3456'),
  Bulgaria: dc('359', 8, 9, '87 123 4567'),
  'Burkina Faso': dc('226', 8, 8, '70 12 34 56'),
  Burundi: dc('257', 8, 8, '79 56 12 34'),
  Cambodia: dc('855', 8, 9, '12 345 678'),
  Cameroon: dc('237', 9, 9, '6 71 23 45 67'),
  Chile: dc('56', 9, 9, '9 6123 4567'),
  Colombia: dc('57', 10, 10, '321 1234567'),
  'Costa Rica': dc('506', 8, 8, '8312 3456'),
  Croatia: dc('385', 8, 9, '91 234 5678'),
  Cuba: dc('53', 8, 8, '5 1234567'),
  Cyprus: dc('357', 8, 8, '96 123456'),
  'Czech Republic': dc('420', 9, 9, '601 123 456'),
  'Dominican Republic': NANP_RULE,
  Ecuador: dc('593', 9, 9, '99 123 4567'),
  'El Salvador': dc('503', 8, 8, '7012 3456'),
  Estonia: dc('372', 7, 8, '5123 4567'),
  Ethiopia: dc('251', 9, 9, '91 123 4567'),
  Fiji: dc('679', 7, 7, '701 2345'),
  Finland: dc('358', 9, 10, '50 123 4567'),
  Greece: dc('30', 10, 10, '691 234 5678'),
  Hungary: dc('36', 8, 9, '20 123 4567'),
  Iceland: dc('354', 7, 7, '611 1234'),
  Iran: dc('98', 10, 10, '912 345 6789'),
  Iraq: dc('964', 10, 10, '790 123 4567'),
  Jamaica: NANP_RULE,
  Jordan: dc('962', 9, 9, '7 9012 3456'),
  Kazakhstan: dc('7', 10, 10, '701 123 4567'),
  Kenya: dc('254', 9, 9, '712 123456'),
  Kuwait: dc('965', 8, 8, '500 12345'),
  Latvia: dc('371', 8, 8, '21 234 567'),
  Lebanon: dc('961', 7, 8, '71 123 456'),
  Lithuania: dc('370', 8, 8, '612 34567'),
  Luxembourg: dc('352', 9, 9, '628 123 456'),
  Maldives: dc('960', 7, 7, '771 2345'),
  Malta: dc('356', 8, 8, '9696 1234'),
  Monaco: dc('377', 8, 9, '6 12 34 56 78'),
  Mongolia: dc('976', 8, 8, '8812 3456'),
  Morocco: dc('212', 9, 9, '650 123456'),
  Myanmar: dc('95', 8, 10, '9 123 4567'),
  Oman: dc('968', 8, 8, '9212 3456'),
  Panama: dc('507', 7, 8, '6123 4567'),
  Peru: dc('51', 9, 9, '912 345 678'),
  Qatar: dc('974', 8, 8, '3312 3456'),
  Romania: dc('40', 9, 9, '712 345 678'),
  Slovakia: dc('421', 9, 9, '912 123 456'),
  Slovenia: dc('386', 8, 8, '31 234 567'),
  Syria: dc('963', 9, 9, '944 567 890'),
  Taiwan: dc('886', 9, 9, '912 345 678'),
  Tanzania: dc('255', 9, 9, '712 345 678'),
  'Trinidad & Tobago': NANP_RULE,
  Tunisia: dc('216', 8, 8, '20 123 456'),
  Uganda: dc('256', 9, 9, '712 345678'),
  Ukraine: dc('380', 9, 9, '50 123 4567'),
  Uruguay: dc('598', 8, 8, '94 231 234'),
  Uzbekistan: dc('998', 9, 9, '90 123 45 67'),
  Venezuela: dc('58', 10, 10, '412 1234567'),
  Yemen: dc('967', 9, 9, '712 345 678'),
  Zambia: dc('260', 9, 9, '95 5123456'),
  Zimbabwe: dc('263', 9, 9, '71 234 5678'),
};

Object.assign(COUNTRY_PHONE_RULES, EXTRA_COUNTRY_PHONE_RULES);

const DEFAULT_PHONE_RULE: CountryPhoneRule = {
  minDigits: 7,
  maxDigits: 15,
  example: '+123 456 7890',
};

export function getCountryPhoneRule(country: string | null | undefined): CountryPhoneRule {
  const key = String(country || '').trim();
  return COUNTRY_PHONE_RULES[key] || DEFAULT_PHONE_RULE;
}

export function getCountryPhoneExample(country: string | null | undefined): string {
  return getCountryPhoneRule(country).example;
}

export function getCountryPhonePlaceholder(country: string | null | undefined): string {
  return `e.g. ${getCountryPhoneExample(country)}`;
}

function formatNanpPhone(digits: string): string {
  return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`;
}

function formatGroupedBy2(digits: string): string {
  const parts: string[] = [];
  for (let i = 0; i < digits.length; i += 2) {
    parts.push(digits.slice(i, i + 2));
  }
  return parts.join(' ');
}

/** Strip non-digits and optional country dial code for the selected country. */
export function stripPhoneDigitsForCountry(value: unknown, country?: string | null): string {
  let digits = String(value ?? '').replace(/\D/g, '');
  if (!digits) {
    return '';
  }

  const rule = getCountryPhoneRule(country);
  if (rule.dialCode && digits.startsWith(rule.dialCode) && digits.length > rule.dialCode.length + rule.minDigits - 1) {
    digits = digits.slice(rule.dialCode.length);
  }

  // NANP trunk prefix
  if ((country === 'Canada' || country === 'United States of America') && digits.length === 11 && digits.startsWith('1')) {
    digits = digits.slice(1);
  }

  // UK / AU local trunk "0"
  if ((country === 'United Kingdom' || country === 'Australia') && digits.startsWith('0') && digits.length > rule.minDigits) {
    digits = digits.replace(/^0+/, '');
  }

  return digits;
}

export function isValidCountryPhone(value: unknown, country?: string | null): boolean {
  const raw = String(value ?? '').trim();
  if (!raw) {
    return false;
  }
  const rule = getCountryPhoneRule(country);
  const digits = stripPhoneDigitsForCountry(raw, country);
  if (digits.length < rule.minDigits || digits.length > rule.maxDigits) {
    return false;
  }
  if (rule.nationalPattern && !rule.nationalPattern.test(digits)) {
    return false;
  }
  return true;
}

/** India allows a 10-digit mobile; every other country must include its calling code (e.g. +1, +44). */
export function isValidCountryPhoneWithDialCode(value: unknown, country?: string | null): boolean {
  const key = String(country || '').trim();
  if (!key) {
    return false;
  }
  if (key === 'India') {
    return isValidCountryPhone(value, country);
  }
  const rule = getCountryPhoneRule(country);
  const digits = String(value ?? '').replace(/\D/g, '');
  if (!rule.dialCode || !digits.startsWith(rule.dialCode)) {
    return false;
  }
  const nationalLen = digits.length - rule.dialCode.length;
  if (nationalLen < rule.minDigits || nationalLen > rule.maxDigits) {
    return false;
  }
  return isValidCountryPhone(value, country);
}

export function getCountryPhoneDialExample(country: string | null | undefined): string {
  const key = String(country || '').trim();
  const rule = getCountryPhoneRule(country);
  if (key === 'India' || !rule.dialCode) {
    return rule.example;
  }
  return '+' + rule.dialCode + ' ' + rule.example.replace(/^\+\d+\s*/, '');
}

export function countryPhoneValidator(country: string): ValidatorFn {
  return (control: AbstractControl): ValidationErrors | null => {
    const raw = control.value;
    if (raw == null || String(raw).trim() === '') {
      return null;
    }
    return isValidCountryPhone(raw, country) ? null : { countryPhone: { country, example: getCountryPhoneExample(country) } };
  };
}

export function countryPhoneRequiredValidators(country: string): ValidatorFn[] {
  return [Validators.required, countryPhoneValidator(country)];
}

export function formatCountryPhone(value: unknown, country?: string | null): string {
  const raw = value == null ? '' : String(value).trim();
  if (!raw) {
    return '';
  }
  const rule = getCountryPhoneRule(country);
  const digits = stripPhoneDigitsForCountry(raw, country);
  if (digits.length < rule.minDigits || digits.length > rule.maxDigits) {
    return raw;
  }
  if (rule.format) {
    return rule.format(digits);
  }
  return raw;
}
