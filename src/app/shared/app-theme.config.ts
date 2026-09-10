export interface AppTheme {
  id: string;
  name: string;
  description: string;
  swatch: string;
  accent: string;
  category: 'pharma' | 'corporate' | 'luxury' | 'clinical';
  recommended?: boolean;
}

export const APP_THEME_STORAGE_KEY = 'app_global_theme';

/** Only these five themes are offered in the UI. */
export const APP_THEMES: AppTheme[] = [
  {
    id: 'pharmacopoeia-blue',
    name: 'Pharmacopoeia Blue',
    description: '',
    swatch: 'linear-gradient(135deg, #0d3b66 0%, #1b4f72 100%)',
    accent: '#1b4f72',
    category: 'pharma',
    recommended: true,
  },
  {
    id: 'classic-navy',
    name: 'Classic Navy',
    description: '',
    swatch: 'linear-gradient(135deg, #004a70 0%, #0ea5e9 100%)',
    accent: '#004a70',
    category: 'pharma',
  },
  {
    id: 'gmp-graphite',
    name: 'GMP Graphite',
    description: '',
    swatch: 'linear-gradient(135deg, #1e293b 0%, #475569 100%)',
    accent: '#334155',
    category: 'pharma',
  },
  {
    id: 'royal-indigo',
    name: 'Royal Indigo',
    description: '',
    swatch: 'linear-gradient(135deg, #312e81 0%, #6366f1 100%)',
    accent: '#4338ca',
    category: 'corporate',
  },
  {
    id: 'platinum-slate',
    name: 'Platinum Slate',
    description: '',
    swatch: 'linear-gradient(135deg, #334155 0%, #64748b 100%)',
    accent: '#475569',
    category: 'corporate',
  },
];

export const DEFAULT_APP_THEME_ID = 'pharmacopoeia-blue';

export function findAppTheme(id: string): AppTheme | undefined {
  return APP_THEMES.find((t) => t.id === id);
}
