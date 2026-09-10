export interface EngNavModule {
  route: string | any[];
  icon: string;
  label: string;
  id?: string;
  subtitle?: string;
  queryParams?: Record<string, unknown>;
  exact?: boolean;
  ngIf?: string;
}

export interface EngDashboardSection {
  label: string;
  modules: EngNavModule[];
}
