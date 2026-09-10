import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-raise-change-control',
  templateUrl: './raise-change-control.component.html',
  styleUrls: ['./raise-change-control.component.css']
})
export class RaiseChangeControlComponent implements OnInit {
  list: any[] = [];
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadList();
  }

  loadList(): void {
    this.loading = true;
    this.service.get('master/test.php?type=getRevisionForChangeControl').subscribe((response: any) => {
      this.list = Array.isArray(response) ? response : (response?.data ? response.data : []);
      this.loading = false;
    }, () => {
      this.service.get('master/test.php?type=getTestsLog').subscribe((res: any) => {
        const arr = Array.isArray(res) ? res : [];
        this.list = arr.map((r: any) => ({ ...r, revision_approved_by_qa: false }));
        this.loading = false;
      }, () => { this.loading = false; });
    });
  }

  get filteredList(): any[] {
    if (!this.list.length) return [];
    if (!this.searchQuery?.trim()) return this.list;
    const q = this.searchQuery.toLowerCase().trim();
    return this.list.filter((t: any) =>
      Object.values(t).some(v => v != null && String(v).toLowerCase().includes(q))
    );
  }

  isRaiseCcEnabled(row: any): boolean {
    return row && (row.revision_approved_by_qa === true || row.revision_approved_by_qa === 'Yes' || row.revision_approved_by_qa === '1');
  }

  raiseChangeControl(row: any): void {
    if (!this.isRaiseCcEnabled(row)) return;
    const revisionId = row.revision_request_id || row.id;
    const testId = row.test_id || row.id;
    this.router.navigate(['/qa/qms/change-control/new'], {
      queryParams: { source: 'master_test_revision', revision_id: revisionId, test_id: testId }
    });
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }
}
