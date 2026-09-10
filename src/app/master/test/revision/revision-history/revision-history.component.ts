import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revision-history',
  templateUrl: './revision-history.component.html',
  styleUrls: ['./revision-history.component.css']
})
export class RevisionHistoryComponent implements OnInit {
  list: any[] = [];
  searchQuery = '';
  loading = false;
  detailModalOpen = false;
  selectedRow: any = null;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadList();
  }

  loadList(): void {
    this.loading = true;
    this.service.get('master/test.php?type=getRevisionHistory').subscribe((response: any) => {
      this.list = Array.isArray(response) ? response : (response?.data ? response.data : []);
      this.loading = false;
    }, () => {
      this.service.get('master/test.php?type=getTestsLog').subscribe((res: any) => {
        this.list = Array.isArray(res) ? res : [];
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

  openRevisionDetail(row: any): void {
    this.selectedRow = row;
    this.detailModalOpen = true;
  }

  closeDetailModal(): void {
    this.detailModalOpen = false;
    this.selectedRow = null;
  }

  viewChangeControl(row: any): void {
    const ccId = row.change_control_id || row.cc_id;
    if (ccId) {
      this.router.navigate(['/qa/qms/change-control/log'], { queryParams: { id: ccId } });
    }
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }
}
