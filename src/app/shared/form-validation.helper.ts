import { NgForm } from '@angular/forms';

/**
 * Humanize a control name for display (e.g. 'client_type' -> 'Client Type', 'LglNm' -> 'Legal Name').
 * Pass nameMap for exact labels; otherwise a simple title-case of the key is used.
 */
function humanizeControlName(key: string): string {
  const withSpaces = key.replace(/_/g, ' ').replace(/([A-Z])/g, ' $1').trim();
  return withSpaces.charAt(0).toUpperCase() + withSpaces.slice(1).toLowerCase();
}

/**
 * Collect control names that are invalid (e.g. required but empty) so they can be shown in a message.
 * Uses nameMap for display names when provided; otherwise humanizes the control name.
 */
export function getInvalidRequiredFieldNames(
  form: NgForm | { form?: { controls: { [key: string]: { invalid?: boolean; errors?: Record<string, unknown>; touched?: boolean; dirty?: boolean } } } },
  nameMap?: Record<string, string>
): string[] {
  const formGroup = form?.form;
  if (!formGroup || !formGroup.controls) {
    return [];
  }
  const names: string[] = [];
  const controls = formGroup.controls as {
    [key: string]: {
      invalid?: boolean;
      errors?: Record<string, unknown>;
      touched?: boolean;
      dirty?: boolean;
    };
  };
  for (const key of Object.keys(controls)) {
    const c = controls[key];
    if (!c || !c.invalid || !c.errors) {
      continue;
    }
    const label = nameMap && nameMap[key] ? nameMap[key] : humanizeControlName(key);
    const e = c.errors;
    let detail = label;
    if (e['required'] !== undefined) {
      detail = label;
    } else if (e['minlength']) {
      const req = (e['minlength'] as { requiredLength?: number })?.requiredLength;
      detail = req ? `${label} (minimum ${req} characters)` : label;
    } else if (e['maxlength']) {
      const req = (e['maxlength'] as { requiredLength?: number })?.requiredLength;
      detail = req ? `${label} (maximum ${req} characters)` : label;
    } else if (e['email'] !== undefined) {
      detail = `${label} (invalid email format)`;
    } else if (e['pattern'] !== undefined) {
      detail = `${label} (invalid format)`;
    } else if (e['canadianPhone'] !== undefined) {
      detail = `${label} (invalid phone format)`;
    }
    if (!names.includes(detail)) {
      names.push(detail);
    }
  }
  return names;
}

/**
 * Build alertify message for required fields. Returns message string with field names.
 */
export function getRequiredFieldsMessage(
  form: NgForm | { form?: { controls: { [key: string]: { invalid?: boolean; errors?: Record<string, unknown>; touched?: boolean; dirty?: boolean } } }; valid?: boolean },
  nameMap?: Record<string, string>,
  prefix = 'Required field(s): '
): string {
  const names = getInvalidRequiredFieldNames(form as NgForm, nameMap);
  if (names.length === 0) {
    if ((form as NgForm)?.valid === false) {
      return prefix + 'Please check all fields and try again.';
    }
    return prefix + 'Please fill all required fields.';
  }
  return prefix + names.join(', ');
}

/**
 * HTML bullet list for alertify popup showing invalid fields.
 */
export function getRequiredFieldsAlertHtml(
  form: NgForm,
  nameMap?: Record<string, string>
): string {
  const names = getInvalidRequiredFieldNames(form, nameMap);
  if (!names.length) {
    return '<p>Please check all fields and try again.</p>';
  }
  return (
    '<ul style="text-align:left;margin:10px 0;padding-left:22px;line-height:1.6;">' +
    names.map((name) => `<li>${name}</li>`).join('') +
    '</ul>'
  );
}
