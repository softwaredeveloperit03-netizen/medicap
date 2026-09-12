import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-equipment-work-order-log',
  templateUrl: './log.component.html',
  styleUrls: ['../work-order-form.theme.css'],
})
export class LogComponent implements OnInit {
  department = '';
  results: any[] = [];
  searchQuery = '';
  selected: any = null;
  isView = false;
  interiorChecklist: any = null;
  exteriorChecklist: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.load();
  }

  load(): void {
    this.service
      .get(
        'engineering/equipment_work_order.php?type=getLog&scope=department&department_name=' +
          encodeURIComponent(this.department)
      )
      .subscribe((response) => {
        this.results = (response as any[]) || [];
      });
  }

  get filtered(): any[] {
    if (!this.searchQuery?.trim()) {
      return this.results;
    }
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter((row) =>
      Object.values(row || {}).some((v) => v && String(v).toLowerCase().includes(q))
    );
  }

  closedDisplay(item: any): string {
    if (!item) {
      return '';
    }
    if (item.status === 'CLOSED' && (item.closed_by || item.closed_date)) {
      return [item.closed_by, item.closed_date].filter(Boolean).join(' / ');
    }
    if (item.status === 'TO_QA') {
      return 'Pending QA';
    }
    if (item.verified_by || item.verified_date) {
      return [item.verified_by, item.verified_date].filter(Boolean).join(' / ') + ' (Verified)';
    }
    return '—';
  }

  statusLabel(status: string): string {
    const map: any = {
      TO_ENGG_REVIEW: 'To Engineering Review',
      TO_PART_B: 'Work Performed Pending',
      TO_AREA_CHECKLIST: 'Area Checklist Pending',
      TO_VERIFY: 'Pending Verification',
      TO_QA: 'Pending QA',
      CLOSED: 'Closed',
    };
    return map[status] || status || '';
  }

  parseChecklist(raw: any): any {
    if (!raw) {
      return null;
    }
    if (typeof raw === 'object') {
      return raw;
    }
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  openView(item: any): void {
    this.selected = item;
    this.interiorChecklist = this.parseChecklist(item.interior_checklist_json);
    this.exteriorChecklist = this.parseChecklist(item.exterior_checklist_json);
    this.isView = true;
  }

  closeView(): void {
    this.isView = false;
    this.selected = null;
    this.interiorChecklist = null;
    this.exteriorChecklist = null;
  }

  downloadForm(item: any): void {
    this.service.open(
      'engineering/equipment_work_order.php?type=downloadWorkOrderPdf&id=' +
        item.id +
        '&department_name=' +
        encodeURIComponent(this.department)
    );
  }

  downloadIssuanceLog(): void {
    this.service.open(
      'engineering/equipment_work_order.php?type=downloadIssuanceLogPdf&scope=department&status=ISSUANCE&department_name=' +
        encodeURIComponent(this.department)
    );
  }
}
