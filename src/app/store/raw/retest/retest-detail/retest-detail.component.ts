import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-retest-detail',
  templateUrl: './retest-detail.component.html',
  styleUrls: ['./retest-detail.component.css'],
})
export class RetestDetailComponent implements OnInit {
  results: any[] = [];
  loading = false;
  materialType = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadDetail();
  }

  loadDetail() {
    this.loading = true;
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.loadList('store/raw.php?type=getRetestDetail' + q).subscribe({
      next: (response: any[]) => {
        this.results = response || [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }

  downloadPdf() {
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.open('store/raw.php?type=downloadRetestDetail' + q);
  }

  dueClass(dueDays: number): string {
    if (dueDays == null) return '';
    if (dueDays < 0) return 'retest-overdue';
    if (dueDays === 0) return 'retest-today';
    if (dueDays <= 10) return 'retest-soon';
    return 'retest-future';
  }
}
