import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { statusClass, statusLabel } from '../capa070.utils';

declare let alertify: any;

@Component({
  selector: 'app-capa070-closing',
  templateUrl: './closing.component.html',
  styleUrls: ['../capa070.shared.css'],
})
export class ClosingComponent implements OnInit {
  mode: 'owner' | 'qa' = 'owner';
  results: any[] = [];
  selectedRecord: any = null;
  isView = false;
  closureProof = '';

  constructor(private service: DataAccessService, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.route.queryParams.subscribe((params) => {
      this.mode = params['mode'] === 'qa' ? 'qa' : 'owner';
      this.load();
    });
  }

  load(): void {
    const stage = this.mode === 'qa' ? 'closure' : 'open';
    this.service
      .get('qa/correctivePreventiveAction.php?type=getPendingCapa&stage=' + stage)
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  viewRecord(record: any): void {
    this.service
      .get('qa/correctivePreventiveAction.php?type=getCapaById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.closureProof = '';
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
    this.closureProof = '';
  }

  requestClosure(): void {
    if (!this.selectedRecord?.id) {
      return;
    }
    const proof = (this.closureProof || '').trim();
    if (!proof) {
      alertify.error('Please provide closure proof / notes');
      return;
    }
    this.service
      .post(
        'qa/correctivePreventiveAction.php?type=requestCapaClosure',
        JSON.stringify({ id: this.selectedRecord.id, closure_proof: proof })
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Closure request sent to QA for verification');
          this.closeView();
          this.load();
        } else {
          alertify.error(response?.status || 'Failed to submit closure request');
        }
      });
  }

  approveClosure(): void {
    if (!this.selectedRecord?.id) {
      return;
    }
    this.service
      .post(
        'qa/correctivePreventiveAction.php?type=approveCapaClosure',
        JSON.stringify({ id: this.selectedRecord.id })
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success(
            response.next_status === 'pending_effectiveness'
              ? 'Closure verified. CAPA moved to Effectiveness Check.'
              : 'CAPA closed successfully.'
          );
          this.closeView();
          this.load();
        } else {
          alertify.error(response?.status || 'Failed to verify closure');
        }
      });
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  getStatusLabel(status: string): string {
    return statusLabel(status);
  }
}
