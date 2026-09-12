export function ecvHubPath(): string {
  const path = typeof window !== 'undefined' ? window.location.pathname : '';
  if (path.indexOf('/qc/cleaning-validation') !== -1) {
    return '/qc/cleaning-validation';
  }
  return '/qa/equipment-cleaning-verification';
}

export function ecvHomePath(): string {
  const path = typeof window !== 'undefined' ? window.location.pathname : '';
  if (path.indexOf('/qc/') !== -1) {
    return '/qc';
  }
  return '/qa';
}

export function isSwabSampleType(sampleType: string): boolean {
  return sampleType === 'Product Residue' || sampleType === 'Microbial Swab';
}

export function allowDurationNumber(event: KeyboardEvent): boolean {
  const key = event.key;
  if (['Backspace', 'Tab', 'Enter', 'Escape', 'Delete', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(key) !== -1) {
    return true;
  }
  if (key === '.' && (event.target as HTMLInputElement).value.indexOf('.') === -1) {
    return true;
  }
  return /^\d$/.test(key);
}
