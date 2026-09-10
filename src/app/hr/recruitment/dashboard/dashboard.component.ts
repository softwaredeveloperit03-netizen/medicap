import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  OnInit,
  QueryList,
  ViewChildren,
} from '@angular/core';
import { forkJoin } from 'rxjs';
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

  unreadrinterview = 0;
  unreadrmanreq = 0;
  showAnalytics = true;
  analyticsLoaded = false;
  private charts: Chart[] = [];
  private drawn = false;

  kpis: any = {
    open_requisitions: 0,
    approved_vacancies: 0,
    time_to_hire_days: 0,
    cost_per_hire: 0,
    offer_acceptance_pct: 0,
    joining_ratio_pct: 0,
    interview_success_pct: 0,
    referral_hiring: 0,
    campus_total: 0,
    total_candidates: 0,
    selected: 0,
    joined: 0,
    offers: 0,
  };

  recruitmentStatus: { label: string; value: number }[] = [];
  pipeline: { label: string; value: number }[] = [];
  campusStatus: { label: string; value: number }[] = [];
  sourceWise: { label: string; value: number }[] = [];
  monthlyTrend: { label: string; value: number }[] = [];
  recentRequisitions: any[] = [];
  recentCandidates: any[] = [];

  kpiCards = [
    { key: 'open_requisitions', label: 'Open Requisitions', icon: 'fa-folder-open', tone: 'blue', format: 'number' },
    { key: 'approved_vacancies', label: 'Approved Vacancies', icon: 'fa-check-circle', tone: 'green', format: 'number' },
    { key: 'time_to_hire_days', label: 'Time to Hire', icon: 'fa-hourglass-half', tone: 'amber', format: 'days' },
    { key: 'cost_per_hire', label: 'Cost Per Hire', icon: 'fa-rupee-sign', tone: 'purple', format: 'currency' },
    { key: 'offer_acceptance_pct', label: 'Offer Acceptance %', icon: 'fa-handshake', tone: 'teal', format: 'pct' },
    { key: 'joining_ratio_pct', label: 'Joining Ratio', icon: 'fa-user-plus', tone: 'indigo', format: 'pct' },
    { key: 'interview_success_pct', label: 'Interview Success Rate', icon: 'fa-chart-line', tone: 'pink', format: 'pct' },
    { key: 'referral_hiring', label: 'Referral Hiring', icon: 'fa-people-arrows', tone: 'orange', format: 'number' },
    { key: 'campus_total', label: 'Campus Recruitment', icon: 'fa-university', tone: 'cyan', format: 'number' },
    { key: 'total_candidates', label: 'Candidate Pipeline', icon: 'fa-stream', tone: 'slate', format: 'number' },
  ];

  cards: QcDeptCard[] = [
    {
      id: 'requisition',
      title: 'Manpower Requisition',
      searchText: 'Create and manage manpower requisitions',
      route: 'requisition',
      icon: 'fa-user-plus',
      category: 'Recruitment',
      gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    },
    {
      id: 'interview',
      title: 'Interview Process',
      searchText: 'Manage interview process',
      route: '/hr/recruitment/candidate/new',
      icon: 'fa-user-check',
      category: 'Recruitment',
      gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    },
    {
      id: 'final-hr',
      title: 'Final HR Round',
      searchText: 'Final HR round',
      route: '/hr/recruitment/candidate/finalHrRound',
      icon: 'fa-file-signature',
      category: 'Recruitment',
      gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    },
    {
      id: 'selected',
      title: 'Selected Candidates',
      searchText: 'Selected candidates for further process',
      route: '/hr/recruitment/candidate/log',
      icon: 'fa-clipboard-list',
      category: 'Recruitment',
      gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    },
  ];

  constructor(private service: DataAccessService) {
    try {
      const saved = localStorage.getItem('hr_recruitment_analytics_open');
      if (saved === '0') {
        this.showAnalytics = false;
      }
    } catch (e) {}
  }

  ngOnInit(): void {
    this.getNotification();
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
      localStorage.setItem('hr_recruitment_analytics_open', this.showAnalytics ? '1' : '0');
    } catch (e) {}
    if (this.showAnalytics && this.analyticsLoaded) {
      setTimeout(() => this.drawCharts(), 80);
    } else {
      this.destroyCharts();
      this.drawn = false;
    }
  }

  formatKpi(key: string, format: string): string {
    const v = Number(this.kpis?.[key] ?? 0);
    if (format === 'currency') {
      return '₹ ' + v.toLocaleString('en-IN');
    }
    if (format === 'pct') {
      return v + '%';
    }
    if (format === 'days') {
      return v + ' days';
    }
    return String(v);
  }

  getNotification(): void {
    this.getPendingreqman();
    this.getPendingInterview();
  }

  private setBadge(id: string, value: number): void {
    const card = this.cards.find((c) => c.id === id);
    if (card) {
      card.badge = value > 0 ? value : undefined;
    }
  }

  getPendingreqman(): void {
    this.service.get('notification.php?type=getRequisitionrevisionNotification').subscribe((response: any) => {
      this.unreadrmanreq = Number(response['Pending_req']);
      this.setBadge('requisition', this.unreadrmanreq);
    });
  }

  getPendingInterview(): void {
    this.service.get('notification.php?type=getPendingInterviews').subscribe((response: any) => {
      this.unreadrinterview = Number(response['Pending_interview']);
      this.setBadge('selected', this.unreadrinterview);
    });
  }

  loadAnalytics(): void {
    forkJoin({
      requisitions: this.service.getJsonArray('hr/manpower.php?type=getRequisitionLog'),
      candidates: this.service.getJsonArray('hr/candidate.php?type=getCandidateForInterviewScheduleAndAllocation'),
    }).subscribe(({ requisitions, candidates }) => {
      const reqs = Array.isArray(requisitions) ? requisitions : [];
      const cands = Array.isArray(candidates) ? candidates : [];
      const openReqs = reqs.filter((r) => this.isOpenRequisition(r?.status));
      const approvedReqs = reqs.filter((r) => this.isApprovedRequisition(r?.status));
      const offers = cands.filter((c) => String(c?.isOfferLetter || '').toUpperCase() === 'YES');
      const accepted = offers.filter((c) => {
        const a = String(c?.offeracceptance || '').toLowerCase();
        return a === 'yes' || a === 'accepted' || a === 'accept';
      });
      const joined = cands.filter((c) => !!String(c?.joining_date || '').trim());
      const interviewed = cands.filter((c) => String(c?.isInterviewCompleted || '').toUpperCase() === 'YES'
        || String(c?.status || '').toLowerCase().indexOf('interview') >= 0);
      const selected = cands.filter((c) => String(c?.decision || '').toLowerCase() === 'recommended');
      const hireDays = cands
        .map((c) => this.daysBetween(c?.entry_date, c?.joining_date))
        .filter((d) => d != null) as number[];

      this.kpis = {
        ...this.kpis,
        open_requisitions: openReqs.length,
        approved_vacancies: approvedReqs.reduce((sum, r) => sum + this.vacancyCount(r), 0),
        time_to_hire_days: hireDays.length
          ? Math.round(hireDays.reduce((a, b) => a + b, 0) / hireDays.length)
          : 0,
        cost_per_hire: 0,
        offer_acceptance_pct: offers.length ? Math.round((accepted.length / offers.length) * 100) : 0,
        joining_ratio_pct: offers.length ? Math.round((joined.length / offers.length) * 100) : 0,
        interview_success_pct: interviewed.length ? Math.round((selected.length / interviewed.length) * 100) : 0,
        referral_hiring: cands.filter((c) => /refer/i.test(String(c?.replacementFor || c?.replacement || ''))).length,
        campus_total: cands.filter((c) => /campus/i.test(String(c?.qualification || ''))).length,
        total_candidates: cands.length,
        selected: selected.length,
        joined: joined.length,
        offers: offers.length,
      };
      this.recruitmentStatus = this.countBy(reqs, 'status');
      this.pipeline = this.countBy(cands, 'status');
      this.campusStatus = this.kpis.campus_total
        ? [{ label: 'Campus', value: this.kpis.campus_total }]
        : [];
      this.sourceWise = this.countBy(cands, 'department');
      this.monthlyTrend = this.monthlyCounts(cands);
      this.recentRequisitions = reqs.slice(0, 10);
      this.recentCandidates = cands.slice(0, 10).map((c) => ({
        ...c,
        candidate_name: ((c.firstname || '') + ' ' + (c.lastname || '')).trim() || c.candidate_name || '—',
      }));
      this.analyticsLoaded = true;
      if (this.showAnalytics) {
        setTimeout(() => this.drawCharts(), 60);
      }
    });
  }

  private isOpenRequisition(status: any): boolean {
    const s = String(status || '').toLowerCase();
    return s !== 'approve' && s !== 'approved' && s !== 'reject' && s !== 'rejected';
  }

  private isApprovedRequisition(status: any): boolean {
    const s = String(status || '').toLowerCase();
    return s === 'approve' || s === 'approved';
  }

  private vacancyCount(row: any): number {
    const n = Number(row?.noOfpeopleReq);
    if (!isNaN(n) && n > 0) {
      return n;
    }
    const m = Number(row?.manpowerRequired);
    return !isNaN(m) && m > 0 ? m : 0;
  }

  private daysBetween(from: any, to: any): number | null {
    const a = from ? new Date(from) : null;
    const b = to ? new Date(to) : null;
    if (!a || !b || isNaN(a.getTime()) || isNaN(b.getTime())) {
      return null;
    }
    return Math.max(0, Math.round((b.getTime() - a.getTime()) / 86400000));
  }

  private countBy(rows: any[], key: string): { label: string; value: number }[] {
    const map = new Map<string, number>();
    (rows || []).forEach((row) => {
      const label = String(row?.[key] || 'NA').trim() || 'NA';
      map.set(label, (map.get(label) || 0) + 1);
    });
    return Array.from(map.entries()).map(([label, value]) => ({ label, value }));
  }

  private monthlyCounts(rows: any[]): { label: string; value: number }[] {
    const map = new Map<string, number>();
    (rows || []).forEach((row) => {
      const d = row?.entry_date ? new Date(row.entry_date) : null;
      if (!d || isNaN(d.getTime())) {
        return;
      }
      const label = d.toLocaleString('en-GB', { month: 'short', year: 'numeric' });
      map.set(label, (map.get(label) || 0) + 1);
    });
    return Array.from(map.entries()).map(([label, value]) => ({ label, value }));
  }

  private destroyCharts(): void {
    this.charts.forEach((c) => c.destroy());
    this.charts = [];
  }

  private canvasByRef(ref: string): HTMLCanvasElement | undefined {
    const f = this.canvases?.find((c) => c.nativeElement.getAttribute('data-ref') === ref);
    return f?.nativeElement;
  }

  private drawCharts(): void {
    if (!this.canvases || this.canvases.length === 0) {
      return;
    }
    this.destroyCharts();
    this.drawn = true;
    const colors = ['#2563eb', '#16a34a', '#dc2626', '#d97706', '#7c3aed', '#0891b2', '#db2777', '#64748b'];

    const statusEl = this.canvasByRef('status');
    if (statusEl) {
      this.charts.push(
        new Chart(statusEl, {
          type: 'doughnut',
          data: {
            labels: this.recruitmentStatus.map((x) => x.label),
            datasets: [{ data: this.recruitmentStatus.map((x) => x.value), backgroundColor: colors }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10 } } } },
          },
        })
      );
    }

    const pipeEl = this.canvasByRef('pipeline');
    if (pipeEl) {
      this.charts.push(
        new Chart(pipeEl, {
          type: 'bar',
          data: {
            labels: this.pipeline.map((x) => x.label),
            datasets: [{ label: 'Candidates', data: this.pipeline.map((x) => x.value), backgroundColor: '#0ea5e9' }],
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 10 } } }, y: { ticks: { font: { size: 9 } } } },
          },
        })
      );
    }

    const sourceEl = this.canvasByRef('source');
    if (sourceEl) {
      this.charts.push(
        new Chart(sourceEl, {
          type: 'pie',
          data: {
            labels: this.sourceWise.map((x) => x.label),
            datasets: [{ data: this.sourceWise.map((x) => x.value), backgroundColor: colors }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 8, font: { size: 9 } } } },
          },
        })
      );
    }

    const campusEl = this.canvasByRef('campus');
    if (campusEl) {
      this.charts.push(
        new Chart(campusEl, {
          type: 'bar',
          data: {
            labels: this.campusStatus.map((x) => x.label),
            datasets: [{ label: 'Hires', data: this.campusStatus.map((x) => x.value), backgroundColor: '#7c3aed' }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { ticks: { font: { size: 9 } } }, y: { ticks: { font: { size: 10 } } } },
          },
        })
      );
    }

    const monthEl = this.canvasByRef('monthly');
    if (monthEl) {
      this.charts.push(
        new Chart(monthEl, {
          type: 'line',
          data: {
            labels: this.monthlyTrend.map((x) => x.label),
            datasets: [
              {
                label: 'Candidates',
                data: this.monthlyTrend.map((x) => x.value),
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
  }
}
