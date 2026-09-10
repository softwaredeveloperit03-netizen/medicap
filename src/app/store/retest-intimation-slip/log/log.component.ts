import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-retest-intimation-log',
  templateUrl: './log.component.html',
  styleUrls: ['../retest-intimation-slip.shared.css'],
})
export class LogComponent implements OnInit {
  results: any[] = [];
  loading = false;
  detail: any = null;
  detailLines: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadLog();
  }

  loadLog() {
    this.loading = true;
    this.service.loadList('store/retest_intimation.php?type=getRetestIntimationSlipLog').subscribe({
      next: (rows: any[]) => {
        this.results = rows || [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      },
    });
  }

  view(row: any) {
    this.service.get('store/retest_intimation.php?type=getRetestIntimationSlipDetail&id=' + row.id).subscribe((res: any) => {
      this.detail = res?.header || row;
      this.detailLines = res?.lines || [];
    });
  }

  download(id: number) {
    this.service.open('store/retest_intimation.php?type=downloadRetestIntimationSlip&id=' + id);
  }

  closeView() {
    this.detail = null;
    this.detailLines = [];
  }
}
