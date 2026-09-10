export interface ServiceDescriptionEntry {
  label: string;
  details: string;
  isManual?: boolean;
}

export interface SavedServiceEntry {
  category: string;
  products?: any[];
  descriptions: ServiceDescriptionEntry[];
}

function parseJson<T>(value: any, fallback: T): T {
  if (value == null || value === '') {
    return fallback;
  }
  if (typeof value === 'object') {
    return value as T;
  }
  try {
    return JSON.parse(value) as T;
  } catch {
    return fallback;
  }
}

export function parseClientServiceEntries(client: any): SavedServiceEntry[] {
  if (client?.clientServiceDetails?.length) {
    return client.clientServiceDetails.map((row: any) => ({
      category: row.service_category || row.category || '',
      products: parseJson<any[]>(row.products_json ?? row.products, []),
      descriptions: parseJson<ServiceDescriptionEntry[]>(
        row.descriptions_json ?? row.descriptions,
        []
      ),
    }));
  }

  if (client?.serviceDescriptionData) {
    const parsed = parseJson<any>(client.serviceDescriptionData, {});
    if (Array.isArray(parsed?.savedEntries)) {
      return parsed.savedEntries;
    }
  }

  return [];
}

export function getSavedEntryProductsLabel(entry: SavedServiceEntry): string {
  if (!entry?.products?.length) {
    return 'N/A';
  }
  return entry.products
    .map((product) => product.displayLabel || formatProductLabel(product))
    .join(', ');
}

export function formatProductLabel(product: any): string {
  const name = (product?.product_name || '').toString().trim();
  const code = (product?.product_code || '').toString().trim();
  if (name && code) {
    return `${name} (${code})`;
  }
  return name || code || 'Unnamed Product';
}

export function getDescriptionLines(descriptions: ServiceDescriptionEntry[]): string[] {
  return (descriptions || [])
    .filter((entry) => (entry?.label || '').trim())
    .map((entry) => (entry.details ? `${entry.label} (${entry.details})` : entry.label));
}
