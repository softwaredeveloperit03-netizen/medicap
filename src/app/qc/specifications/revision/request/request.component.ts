import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

interface LogFilter {
  id: string;
  label: string;
  apiType: string;
}

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {
  loading = false;
  searchQuery = '';
  activeFilter = 'raw';
  results: any[] = [];
  revisionRequestBySpec: { [key: string]: any } = {};
  changeControlBySpec: { [key: string]: any } = {};
  viewModalOpen = false;
  selectedSpecData: any = null;

  readonly filters: LogFilter[] = [
    { id: 'raw', label: 'Raw Material', apiType: 'Raw Material' },
    { id: 'packing', label: 'Packing Material', apiType: 'Packing Material' },
    { id: 'finished', label: 'Finished Product', apiType: 'Finish Product' },
    { id: 'inprocess', label: 'Inprocess', apiType: 'Inprocess' },
    { id: 'water', label: 'Water', apiType: 'Water Specification' }
  ];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLogs();
  }

  setFilter(filterId: string): void {
    if (this.activeFilter === filterId) {
      return;
    }
    this.activeFilter = filterId;
    this.loadLogs();
  }

  private loadLogs(): void {
    const selected = this.filters.find((x) => x.id === this.activeFilter);
    const type = selected ? selected.apiType : 'Raw Material';
    this.loading = true;
    this.service
      .get('qc/specification/raw.php?type=getCombinedSpecificationLogs&log_type=' + encodeURIComponent(type))
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.loadRevisionRequestStatuses();
          this.loadChangeControlStatuses();
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        }
      });
  }

  private loadRevisionRequestStatuses(): void {
    const specNos = this.results.map((x: any) => String(x?.specification_no || '').trim()).filter((x: string) => !!x);
    if (!specNos.length) {
      this.revisionRequestBySpec = {};
      return;
    }
    this.service
      .get('qc/specification/raw.php?type=getSpecificationRevisionRequestStatus&spec_nos=' + encodeURIComponent(specNos.join(',')))
      .subscribe((response: any) => {
        this.revisionRequestBySpec = response && typeof response === 'object' ? response : {};
      }, () => (this.revisionRequestBySpec = {}));
  }

  private loadChangeControlStatuses(): void {
    const specNos = this.results.map((x: any) => String(x?.specification_no || '').trim()).filter((x: string) => !!x);
    if (!specNos.length) {
      this.changeControlBySpec = {};
      return;
    }
    this.service
      .get('qc/specification/raw.php?type=getChangeControlStatusForSpecifications&spec_nos=' + encodeURIComponent(specNos.join(',')))
      .subscribe((response: any) => {
        this.changeControlBySpec = response && typeof response === 'object' ? response : {};
      }, () => (this.changeControlBySpec = {}));
  }

  get filteredLogs(): any[] {
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.results;
    }
    return this.results.filter((row: any) =>
      Object.keys(row || {}).some((key) => row[key] != null && String(row[key]).toLowerCase().includes(q))
    );
  }

  private parseDate(value: any): Date | null {
    if (!value) {
      return null;
    }
    const d = new Date(value);
    if (isNaN(d.getTime())) {
      return null;
    }
    d.setHours(0, 0, 0, 0);
    return d;
  }

  private getDaysToReview(row: any): number | null {
    const reviewDate = this.parseDate(row?.review_date);
    if (!reviewDate) {
      return null;
    }
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffMs = reviewDate.getTime() - today.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24));
  }

  isRevisionDue(row: any): boolean {
    const days = this.getDaysToReview(row);
    return days != null && days <= 15;
  }

  getRevisionRequestState(row: any): string {
    const specNo = String(row?.specification_no || '').trim();
    return String(this.revisionRequestBySpec[specNo]?.status || '').trim();
  }

  private getChangeControlState(row: any): string {
    const specNo = String(row?.specification_no || '').trim();
    return String(this.changeControlBySpec[specNo]?.status || '').trim();
  }

  isChangeControlApproved(row: any): boolean {
    const s = this.getChangeControlState(row).toLowerCase();
    return ['approve', 'approved', 'complete', 'closed', 'close'].includes(s);
  }

  private isChangeControlRaised(row: any): boolean {
    const s = this.getChangeControlState(row).toLowerCase();
    return !!s && !this.isChangeControlApproved(row);
  }

  getRealtimeStatus(row: any): { label: string; cls: string } {
    const req = this.getRevisionRequestState(row).toLowerCase();
    const cc = this.getChangeControlState(row).toLowerCase();
    if (req === 'pending') {
      return { label: 'Revision Request Sent', cls: 'btn-info' };
    }
    if (req === 'accepted' && !cc) {
      return { label: 'Request Approved', cls: 'btn-success' };
    }
    if (cc.includes('for_monitoring') || cc.includes('assessment') || cc.includes('closin')) {
      return { label: 'Specification Changed', cls: 'btn-warning' };
    }
    if (cc.includes('change_closed') || cc.includes('to_qa_finalrev')) {
      return { label: 'Training', cls: 'btn-secondary' };
    }
    if (cc === 'approve' || cc === 'approved') {
      return { label: 'Change Control Approved', cls: 'btn-success' };
    }
    if (cc === 'complete' || cc === 'closed' || cc === 'close') {
      return { label: 'Implemented', cls: 'btn-success' };
    }
    if (this.isChangeControlRaised(row)) {
      return { label: 'Change Control Raised', cls: 'btn-primary' };
    }
    return { label: '-', cls: 'btn' };
  }

  viewSpecification(row: any): void {
    const specNo = String(row?.specification_no || '').trim();
    if (!specNo) {
      return;
    }
    const endpoint = this.isChangeControlApproved(row)
      ? 'qc/specification/raw.php?type=getSpecificationWithDetailsForDraft&specification_no=' + encodeURIComponent(specNo)
      : 'qc/specification/raw.php?type=getSpecificationDraftBySpecNo&specification_no=' + encodeURIComponent(specNo);
    this.service.get(endpoint).subscribe((response: any) => {
      this.selectedSpecData = this.isChangeControlApproved(row) ? response : (response?.draft_data || response);
      this.viewModalOpen = true;
    });
  }
}
