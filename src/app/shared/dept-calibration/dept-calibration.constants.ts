/** Department hub base path → equipment calibration landing route. */
export const DEPT_CALIBRATION_ROUTE_BY_BASE: Record<string, string> = {
  '/qc': '/qc/calibration/equipment',
  '/store': '/store/calibration/equipment',
  '/microbiology': '/microbiology/calibration/equipment',
  '/engineering': '/engineering/calibration/equipment',
  '/production': '/production/equipment-calibration',
  '/qa': '/qa/equipment-calibration',
};

export const DEFAULT_DEPT_CALIBRATION_ROUTE = '/equipment-calibration';

export function resolveDeptCalibrationRoute(deptBase: string): string {
  const base = normalizeDeptBase(deptBase);
  return DEPT_CALIBRATION_ROUTE_BY_BASE[base] || DEFAULT_DEPT_CALIBRATION_ROUTE;
}

export function normalizeDeptBase(value: string): string {
  const path = (value || '').split('?')[0].split('#')[0].trim();
  if (!path || path === '/') {
    return '/';
  }
  const parts = path.split('/').filter(Boolean);
  return parts.length ? `/${parts[0]}` : '/';
}

export function resolveDeptBaseFromUrl(url: string): string {
  return normalizeDeptBase(url);
}
