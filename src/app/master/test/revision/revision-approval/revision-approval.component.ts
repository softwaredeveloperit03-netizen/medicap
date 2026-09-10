import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-revision-approval',
  templateUrl: './revision-approval.component.html',
  styleUrls: ['./revision-approval.component.css']
})
export class RevisionApprovalComponent implements OnInit {
  list: any[] = [];
  searchQuery = '';
  loading = false;
  remarkModalOpen = false;
  selectedRow: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadList();
  }

  loadList(): void {
    this.loading = true;
    this.service.get('master/test.php?type=getRevisionApprovalList').subscribe((response: any) => {
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

  openRemarkModal(row: any): void {
    this.selectedRow = row;
    this.remarkModalOpen = true;
  }

  closeRemarkModal(): void {
    this.remarkModalOpen = false;
    this.selectedRow = null;
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }
}
