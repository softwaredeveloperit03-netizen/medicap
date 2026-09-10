import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-visitor-management',
  templateUrl: './visitor-management.component.html',
  styleUrls: ['./visitor-management.component.css']
})
export class VisitorManagementComponent implements OnInit {
  results: any[] = [];
  loading = false;
  searchQuery = '';

  constructor(
    private router: Router,
    private service: DataAccessService
  ) {}

  ngOnInit(): void {
    this.getGatepassDetails();
  }

  /** Load reception log: only status = VISITOR_IN, ordered by date. After status change, entry no longer here. */
  getGatepassDetails(): void {
    this.loading = true;
    const url = 'security/gatepass.php?type=getGatepassDetailsForReceptionLog';
    this.service.get(url).subscribe({
      next: (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.loading = false;
      }
    });
  }

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
    const q = this.searchQuery.toLowerCase().trim();
    return this.results.filter((r: any) =>
      Object.values(r).some(v => v != null && String(v).toLowerCase().includes(q))
    );
  }

  /** Acknowledge visitor: update status column. Entry then leaves this list (only VISITOR_IN shown). */
  acknowledgeVisitor(id: string | number): void {
    if (id == null) return;
    const emp_id = localStorage.getItem('emp_id') || '';
    const url = 'security/gatepass.php?type=updateReceptionStatus&id=' + encodeURIComponent(String(id)) +
      '&status=' + encodeURIComponent('VISITOR_AT_RECEPTION') +
      '&emp_id=' + encodeURIComponent(emp_id);
    this.service.get(url).subscribe((res: any) => {
      if (res && res.status === 'success') {
        if (typeof alertify !== 'undefined') alertify.success('Status updated to VISITOR_AT_RECEPTION');
        this.getGatepassDetails();
      } else {
        if (typeof alertify !== 'undefined') alertify.error(res?.message || 'Failed to update status');
      }
    });
  }

  goBack(): void {
    this.router.navigate(['/reception']);
  }
}
