import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  QueryList,
  ViewChildren,
} from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChildren('chartCanvas') canvases!: QueryList<ElementRef<HTMLCanvasElement>>;

  showAnalytics = true;
  analyticsLoaded = false;
  private charts: Chart[] = [];

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights: any;

  kpis: any = {
    total_employees: 0,
    departments: 0,
    male: 0,
    female: 0,
    permanent: 0,
    contract: 0,
    designations: 0,
    locations: 0,
  };

  byDepartment: { label: string; value: number }[] = [];
  byPlant: { label: string; value: number }[] = [];
  byLocation: { label: string; value: number }[] = [];
  byGrade: { label: string; value: number }[] = [];
  byDesignation: { label: string; value: number }[] = [];
  byGender: { label: string; value: number }[] = [];
  byAge: { label: string; value: number }[] = [];
  byQualification: { label: string; value: number }[] = [];
  byExperience: { label: string; value: number }[] = [];
  byType: { label: string; value: number }[] = [];
  orgChart: { label: string; value: number; children?: { label: string; value: number }[] }[] = [];
  heatMap: { columns: string[]; rows: { department: string; cells: any; total: number }[] } = {
    columns: [],
    rows: [],
  };
  recentEmployees: any[] = [];
  heatMax = 1;

  kpiCards = [
    { key: 'total_employees', label: 'Employee Distribution', icon: 'fa-users', tone: 'blue' },
    { key: 'departments', label: 'Department-wise', icon: 'fa-sitemap', tone: 'teal' },
    { key: 'locations', label: 'Location-wise', icon: 'fa-map-marker-alt', tone: 'amber' },
    { key: 'designations', label: 'Designation-wise', icon: 'fa-id-badge', tone: 'purple' },
    { key: 'male', label: 'Male', icon: 'fa-male', tone: 'indigo' },
    { key: 'female', label: 'Female', icon: 'fa-female', tone: 'pink' },
    { key: 'permanent', label: 'Permanent', icon: 'fa-user-tie', tone: 'green' },
    { key: 'contract', label: 'Contract', icon: 'fa-file-contract', tone: 'orange' },
  ];

  cards: QcDeptCard[] = [
    {
      id: 'approval',
      title: 'Employee Approval',
      route: 'approval',
      icon: 'fa-calendar-check',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'list',
      title: 'Employee List',
      route: 'list',
      icon: 'fa-list',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)',
    },
    {
      id: 'change-dept-dash',
      title: 'Inter Dept. Transfer',
      route: 'change_dept_dash',
      icon: 'fa-list',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)',
    },
    {
      id: 'resigned',
      title: 'Resigned Employee List',
      route: 'resigned',
      icon: 'fa-list',
      category: 'Modules',
      gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)',
    },
  ];

  constructor(private service: DataAccessService) {
    try {
      if (localStorage.getItem('hr_employees_analytics_open') === '0') {
        this.showAnalytics = false;
      }
    } catch (e) {}
  }

  ngOnInit(): void {
    this.get_rights();
    this.loadAnalytics();
  }

  ngAfterViewInit(): void {
    this.canvases?.changes?.subscribe(() => {
      if (this.showAnalytics && this.analyticsLoaded) {
        setTimeout(() => this.drawCharts(), 40);
      }
    });
  }

  ngOnDestroy(): void {
    this.destroyCharts();
  }

  toggleAnalytics(): void {
    this.showAnalytics = !this.showAnalytics;
    try {
      localStorage.setItem('hr_employees_analytics_open', this.showAnalytics ? '1' : '0');
    } catch (e) {}
    if (this.showAnalytics && this.analyticsLoaded) {
      setTimeout(() => this.drawCharts(), 80);
    } else {
      this.destroyCharts();
    }
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          encodeURIComponent(localStorage.getItem('emp_id') || '') +
          '&dep_name=' +
          encodeURIComponent(localStorage.getItem('department') || '')
      )
      .subscribe({
        next: (response) => {
          this.rights = response;
          const r = this.service.parseEmpRightsResponse(response);
          this.isuser = r.isuser;
          this.ischecker = r.ischecker;
          this.isapprover = r.isapprover;
        },
        error: () => {
          this.rights = [];
        },
      });
  }

  loadAnalytics(): void {
    this.service.get('hr/employee_analytics.php?type=getEmployeeDashboardAnalytics').subscribe((res: any) => {
      if (!res || res.status !== 'success') {
        return;
      }
      this.kpis = { ...this.kpis, ...(res.kpis || {}) };
      this.byDepartment = res.by_department || [];
      this.byPlant = res.by_plant || [];
      this.byLocation = res.by_location || [];
      this.byGrade = res.by_grade || [];
      this.byDesignation = res.by_designation || [];
      this.byGender = res.by_gender || [];
      this.byAge = res.by_age || [];
      this.byQualification = res.by_qualification || [];
      this.byExperience = res.by_experience || [];
      this.byType = res.by_type || [];
      this.orgChart = res.org_chart || [];
      this.heatMap = res.heat_map || { columns: [], rows: [] };
      this.recentEmployees = res.tables?.recent || [];
      this.heatMax = 1;
      (this.heatMap.rows || []).forEach((row) => {
        Object.values(row.cells || {}).forEach((v: any) => {
          this.heatMax = Math.max(this.heatMax, Number(v) || 0);
        });
      });
      this.analyticsLoaded = true;
      if (this.showAnalytics) {
        setTimeout(() => this.drawCharts(), 60);
      }
    });
  }

  treemapStyle(item: { value: number }, list: { value: number }[]): { [k: string]: string } {
    const total = list.reduce((s, x) => s + (Number(x.value) || 0), 0) || 1;
    const pct = Math.max(8, Math.round(((Number(item.value) || 0) / total) * 100));
    return { flex: `1 1 ${pct}%`, minWidth: pct + '%' };
  }

  heatColor(v: number): string {
    const t = Math.min(1, (Number(v) || 0) / (this.heatMax || 1));
    const alpha = 0.15 + t * 0.85;
    return `rgba(14, 116, 144, ${alpha})`;
  }

  private destroyCharts(): void {
    this.charts.forEach((c) => c.destroy());
    this.charts = [];
  }

  private canvasByRef(ref: string): HTMLCanvasElement | undefined {
    return this.canvases?.find((c) => c.nativeElement.getAttribute('data-ref') === ref)?.nativeElement;
  }

  private drawCharts(): void {
    if (!this.canvases?.length) {
      return;
    }
    this.destroyCharts();
    const colors = ['#2563eb', '#16a34a', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#64748b', '#0f766e', '#ea580c'];

    const pie = this.canvasByRef('genderPie');
    if (pie) {
      this.charts.push(
        new Chart(pie, {
          type: 'pie',
          data: {
            labels: this.byGender.map((x) => x.label),
            datasets: [{ data: this.byGender.map((x) => x.value), backgroundColor: colors }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } },
          },
        })
      );
    }

    const typeDoughnut = this.canvasByRef('typePie');
    if (typeDoughnut) {
      this.charts.push(
        new Chart(typeDoughnut, {
          type: 'doughnut',
          data: {
            labels: this.byType.map((x) => x.label),
            datasets: [{ data: this.byType.map((x) => x.value), backgroundColor: ['#16a34a', '#ea580c'] }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } },
          },
        })
      );
    }

    const ageBar = this.canvasByRef('ageBar');
    if (ageBar) {
      this.charts.push(
        new Chart(ageBar, {
          type: 'bar',
          data: {
            labels: this.byAge.map((x) => x.label),
            datasets: [{ label: 'Employees', data: this.byAge.map((x) => x.value), backgroundColor: '#7c3aed' }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 9 } } } },
          },
        })
      );
    }

    const qualBar = this.canvasByRef('qualBar');
    if (qualBar) {
      this.charts.push(
        new Chart(qualBar, {
          type: 'bar',
          data: {
            labels: this.byQualification.map((x) => x.label),
            datasets: [{ label: 'Count', data: this.byQualification.map((x) => x.value), backgroundColor: '#0ea5e9' }],
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 8 } } } },
          },
        })
      );
    }

    const expLine = this.canvasByRef('expLine');
    if (expLine) {
      this.charts.push(
        new Chart(expLine, {
          type: 'line',
          data: {
            labels: this.byExperience.map((x) => x.label),
            datasets: [
              {
                label: 'Experience',
                data: this.byExperience.map((x) => x.value),
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,.15)',
                fill: true,
                tension: 0.35,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
          },
        })
      );
    }

    const desigBar = this.canvasByRef('desigBar');
    if (desigBar) {
      this.charts.push(
        new Chart(desigBar, {
          type: 'bar',
          data: {
            labels: this.byDesignation.slice(0, 10).map((x) => x.label),
            datasets: [
              {
                label: 'Headcount',
                data: this.byDesignation.slice(0, 10).map((x) => x.value),
                backgroundColor: '#db2777',
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 8 }, maxRotation: 45 } }, y: { ticks: { font: { size: 9 } } } },
          },
        })
      );
    }
  }
}
