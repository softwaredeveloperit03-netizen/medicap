import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qc-retest-intimation-receive',
  templateUrl: './receive.component.html',
})
export class ReceiveComponent implements OnInit {
  results: any[] = [];
  loading = false;
  selected: any = null;
  receive_remarks = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadPending();
  }

  loadPending() {
    this.loading = true;
    this.service.loadList('qc/retest_intimation.php?type=getPendingRetestIntimationSlips').subscribe({
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

  openReceive(row: any) {
    this.selected = row;
    this.receive_remarks = '';
  }

  confirmReceive() {
    if (!this.selected?.id) {
      return;
    }
    this.service.post('qc/retest_intimation.php?type=receiveRetestIntimationSlip', JSON.stringify({
      slip_id: this.selected.id,
      receive_remarks: this.receive_remarks,
    })).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res.msg || 'Slip received.');
        this.selected = null;
        this.loadPending();
      } else {
        alertify.error(res?.msg || 'Failed to receive slip.');
      }
    });
  }

  download(id: number) {
    this.service.open('qc/retest_intimation.php?type=downloadRetestIntimationSlip&id=' + id);
  }
}
