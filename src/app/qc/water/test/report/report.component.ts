import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-water-test-ar-report',
  templateUrl: './report.component.html',
  styleUrls: ['./report.component.css'],
  providers: [DatePipe]
})
export class ReportComponent implements OnInit {
  isView = false;
  loading = false;
  results: any[] = [];
  results1: any[] = [];
  water_type = '';
  selectedPlan: any = {};
  from_date = '';
  to_date = '';
  sample_qty = 0;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
  }

  ngOnInit() {
    this.getTestingReport();
  }

  getTestingReport() {
    this.loading = true;
    this.service
      .get(
        'qc/water.php?type=getTestingReport&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe({
        next: (response: any) => {
          this.results = Array.isArray(response) ? response : [];
          this.results1 = this.results;
          this.loading = false;
          if (this.water_type) {
            this.filterStock();
          }
        },
        error: () => {
          this.results = [];
          this.results1 = [];
          this.loading = false;
        }
      });
  }

  view(result: any) {
    this.selectedPlan = result || {};
    this.sample_qty =
      (+this.selectedPlan['chemical_qty'] || 0) +
      (+this.selectedPlan['microbiology_qty'] || 0);
    this.isView = true;
  }

  download() {
    this.service.open(
      'qc/water.php?type=downloadTestingARLog&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }

  downloadReport() {
    if (!this.selectedPlan || !this.selectedPlan['id']) {
      return;
    }
    this.service.open(
      'qc/water.php?type=downloadTestingARReport&id=' + this.selectedPlan['id']
    );
  }

  filterStock() {
    const q = (this.water_type || '').trim().toUpperCase();
    if (!q) {
      this.results = this.results1 || [];
      return;
    }
    this.results = (this.results1 || []).filter((data: any) =>
      String(data.water_type || '')
        .toUpperCase()
        .includes(q)
    );
  }

  clear() {
    this.water_type = '';
    this.filterStock();
  }
}
