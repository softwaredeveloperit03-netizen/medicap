/** Display labels for bmr_for (stored value remains Generic | Product). */
export const BMR_FOR = {
  GENERIC: 'Generic',
  PRODUCT: 'Product',
} as const;

export type BmrForType = typeof BMR_FOR.GENERIC | typeof BMR_FOR.PRODUCT;

export function bmrForLabel(value: string | undefined | null): string {
  return value === BMR_FOR.PRODUCT ? 'Product BMR' : 'Generic BMR';
}

/** Generic BMR = dosage form + process type template (not generic drug / INN). */
export const GENERIC_BMR_DEFINITION =
  'Each dosage form and process type has its own Generic BMR — a draft template not tied to one product. It can be bound to any product later.';

export const GENERIC_BMR_SHORT =
  'Dosage form · process type template — bind to any product (not generic drug)';

export function genericBmrRowHint(dosageForm?: string, processType?: string): string {
  const parts = [dosageForm, processType].filter(Boolean);
  if (parts.length) {
    return `Template for ${parts.join(' · ')} — bind any product later`;
  }
  return 'Template BMR — bind any product later';
}

export function genericBmrBannerHint(dosageForm?: string, processType?: string): string {
  const pair = [dosageForm, processType].filter(Boolean).join(' · ');
  return pair
    ? `· ${pair} template — bind to any product after completion (not generic drug)`
    : '· Dosage + process template — bind to any product after completion (not generic drug)';
}
