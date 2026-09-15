import { Component, OnInit } from '@angular/core';
import {
  buildSidebarTabsFromCards,
  QC_CARD_GRADIENTS,
} from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.constants';
import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { DataAccessService } from 'src/app/data-access.service';

const G = QC_CARD_GRADIENTS;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  readonly cards: QcDeptCard[] = [
    {
      id: 'material-master-data',
      title: 'Material Master Data',
      route: '/planning/material-master-data',
      icon: 'fa-clock',
      category: 'Planning',
      gradient: G.emerald,
    },
    {
      id: 'receivepofo',
      title: 'Receive FO/PO',
      route: '/planning/Receivepofo',
      icon: 'fa-inbox',
      category: 'Planning',
      gradient: G.teal,
    },
    {
      id: 'generatewo',
      title: 'Generate Work Order',
      route: '/planning/Generatewo',
      icon: 'fa-file-alt',
      category: 'Planning',
      gradient: G.blue,
    },
    {
      id: 'shortages',
      title: 'Shortage Analysis',
      route: '/planning/Shortages',
      icon: 'fa-chart-bar',
      category: 'Planning',
      gradient: G.amber,
    },
    {
      id: 'woplanninghub',
      title: 'Indent Confirm / Can Plan',
      route: '/planning/WoPlanningHub',
      icon: 'fa-tasks',
      category: 'Planning',
      gradient: G.violet,
    },
    {
      id: 'canplan',
      title: 'Process Plan',
      route: '/planning/stplan/stStp/Canplan',
      icon: 'fa-project-diagram',
      category: 'STP',
      gradient: G.indigo,
    },
    {
      id: 'verifystock',
      title: 'Verify & Book Stock',
      route: '/planning/stplan/stStp/VerifyStock',
      icon: 'fa-check-circle',
      category: 'STP',
      gradient: G.emerald,
    },
    {
      id: 'forplan',
      title: 'Line Booking',
      route: '/planning/stplan/stStp/Forplan',
      icon: 'fa-vial',
      category: 'STP',
      gradient: G.navy,
    },
    {
      id: 'lineapproval',
      title: 'Line Approval',
      route: '/planning/stplan/stStp/Lineapproval',
      icon: 'fa-clipboard-check',
      category: 'STP',
      gradient: G.slate,
    },
  ];

  readonly sidebarTabs = buildSidebarTabsFromCards(this.cards);

  kpiLoading = false;
  kpis: any = null;

  traceWo = 'BO002';
  traceMaterial = 'RM0129';
  traceLoading = false;
  traceError = '';
  traceStages: any[] = [];
  traceLast: any = null;
  showTrace = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadKpis();
  }

  loadKpis(): void {
    this.kpiLoading = true;
    this.service.get('mrp/indentsconfirmation.php?type=getMrpDashboardStats').subscribe({
      next: (res: any) => {
        this.kpis = res || {};
        this.kpiLoading = false;
      },
      error: () => {
        this.kpis = null;
        this.kpiLoading = false;
      },
    });
  }

  loadTrace(): void {
    const wo = (this.traceWo || '').trim();
    const mat = (this.traceMaterial || '').trim();
    if (!wo && !mat) {
      this.traceError = 'Enter work order and/or material code';
      return;
    }
    this.traceLoading = true;
    this.traceError = '';
    this.showTrace = true;
    let url = 'mrp/indentsconfirmation.php?type=getMrpTraceability';
    if (wo) {
      url += '&workorder_no=' + encodeURIComponent(wo);
    }
    if (mat) {
      url += '&material_code=' + encodeURIComponent(mat);
    }
    this.service.get(url).subscribe({
      next: (res: any) => {
        this.traceLoading = false;
        if (res?.status === 'success') {
          this.traceStages = res.stages || [];
          this.traceLast = res.last_stage || null;
        } else {
          this.traceStages = [];
          this.traceLast = null;
          this.traceError = res?.message || 'Trace failed';
        }
      },
      error: () => {
        this.traceLoading = false;
        this.traceStages = [];
        this.traceError = 'Failed to load traceability';
      },
    });
  }
}
