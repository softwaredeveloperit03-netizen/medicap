import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-update-form',
  templateUrl: './update-form.component.html',
  styleUrls: ['./update-form.component.css']
})
export class UpdateFormComponent implements OnInit {
  list: any[] = [];
  searchQuery = '';
  loading = false;

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadList();
  }

  loadList(): void {
    this.loading = true;
    this.service.get('master/test.php?type=getRevisionForUpdateForm').subscribe((response: any) => {
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

  openUpdateForm(row: any): void {
    const testId = row.test_id || row.id;
    this.router.navigate(['/qc/moa/methods/new/' + testId]);
  }

  getTestStatus(result: any): string {
    if (!result || result.status == null) return 'Active';
    const s = String(result.status);
    if (s === 'In-Active') return 'In-Active';
    if (s === 'Absolute') return 'Absolute';
    return 'Active';
  }
}
