import { ActivatedRoute } from '@angular/router';

export interface EquipmentDeptCalibrationConfig {
  performDepartment: string;
  closeRoute: string;
  /** When true, performDepartment and closeRoute come from session/localStorage. */
  useSessionDepartment?: boolean;
}

export function readEquipmentDeptCalibrationConfig(route: ActivatedRoute): EquipmentDeptCalibrationConfig {
  for (const segment of route.pathFromRoot) {
    const data = segment.snapshot.data || {};
    if (data.performDepartment || data.useSessionDepartment) {
      if (data.useSessionDepartment) {
        const dept = (typeof localStorage !== 'undefined' ? localStorage.getItem('department') : null) || '';
        const base = resolveSessionCloseRoute(dept);
        return {
          performDepartment: dept,
          closeRoute: base,
          useSessionDepartment: true,
        };
      }
      return {
        performDepartment: String(data.performDepartment),
        closeRoute: String(data.closeRoute || '/'),
      };
    }
  }
  return { performDepartment: '', closeRoute: '/' };
}

function resolveSessionCloseRoute(department: string): string {
  const map: Record<string, string> = {
    'Quality Control': '/qc',
    'Quality Assurance': '/qa',
    Store: '/store',
    Microbiology: '/microbiology',
    Engineering: '/engineering',
    Production: '/production',
    'R AND D': '/rnd',
    Purchase: '/purchase',
    HR: '/hr',
    IPQC: '/ipqc',
    EHS: '/ehs',
    Planning: '/planning',
    IT: '/it',
    Management: '/management',
    Marketing: '/marketing',
    Dispatch: '/dispatch',
    Reception: '/reception',
    Accounts: '/accounts',
    NPD: '/npd',
    Security: '/security',
    Admin: '/admin',
  };
  return map[department] || '/';
}
