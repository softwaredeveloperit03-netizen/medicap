import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css', '../retest-detail/retest-detail.component.css'],
})
export class AwaitingComponent implements OnInit {
  results: any[] = [];
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingRetests();
  }

  getPendingRetests() {
    this.loading = true;
    const q =
      'store/raw.php?type=getPendingRetests&material_type=' + encodeURIComponent('Raw Material');
    this.service.getJsonArray(q).subscribe({
      next: (response: any[]) => {
        this.results = response || [];
        this.loading = false;
        if (!this.results.length) {
          alertify.warning('No retest entries yet. Approve QC testing first, then refresh this page.');
        }
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load retest awaiting list.');
      },
    });
  }

  dueClass(dueDays: number): string {
    if (dueDays == null) return '';
    if (dueDays < 0) return 'retest-overdue';
    if (dueDays === 0) return 'retest-today';
    if (dueDays <= 10) return 'retest-soon';
    return 'retest-future';
  }
}
