import { countries } from 'countries-list';

/** Full English country names from `countries-list`, merged with API rows (deduped, A→Z). */
export function buildCompanyCountryOptions(
  apiRows: { country?: string }[] | null | undefined
): { country: string }[] {
  const map = new Map<string, string>();
  Object.values(countries).forEach((c) => {
    if (c?.name) {
      map.set(c.name.toLowerCase(), c.name);
    }
  });
  (apiRows || []).forEach((r) => {
    const n = r?.country?.trim();
    if (n) {
      map.set(n.toLowerCase(), n);
    }
  });
  return Array.from(map.values())
    .sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }))
    .map((country) => ({ country }));
}
