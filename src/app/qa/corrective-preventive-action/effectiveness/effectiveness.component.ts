import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { getEmpDisplayName, stampNow, statusClass, statusLabel } from '../capa070.utils';

declare let alertify: any;

@Component({
  selector: 'app-capa070-effectiveness',
  templateUrl: './effectiveness.component.html',
  styleUrls: ['../capa070.shared.css'],
  providers: [DatePipe],
})
export class EffectivenessComponent implements OnInit {
  results: any[] = [];
  selectedRecord: any = null;
  isView = false;

  effCorrectiveResult = 'N/A';
  effCorrectiveAdditional = '';
  effPreventativeResult = 'N/A';
  effPreventativeAdditional = '';
  effDeptBy = '';
  effQaBy = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.service
      .get('qa/correctivePreventiveAction.php?type=getPendingCapa&stage=effectiveness')
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
          this.effCorrectiveResult = response.eff_corrective_result || 'N/A';
          this.effCorrectiveAdditional = response.eff_corrective_additional || '';
          this.effPreventativeResult = response.eff_preventative_result || 'N/A';
          this.effPreventativeAdditional = response.eff_preventative_additional || '';
          this.effDeptBy = response.eff_dept_by || '';
          this.effQaBy = response.eff_qa_by || '';
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  stampDept(): void {
    this.effDeptBy = stampNow(this.datePipe);
  }

  stampQa(): void {
    this.effQaBy = stampNow(this.datePipe);
  }

  save(): void {
    if (!this.selectedRecord?.id) {
      return;
    }
    if (!this.effQaBy) {
      alertify.error('QA stamp is required to complete the effectiveness check');
      return;
    }
    const payload = {
      id: this.selectedRecord.id,
      eff_corrective_result: this.effCorrectiveResult,
      eff_corrective_additional: this.effCorrectiveAdditional,
      eff_preventative_result: this.effPreventativeResult,
      eff_preventative_additional: this.effPreventativeAdditional,
      eff_dept_by: this.effDeptBy,
      eff_qa_by: this.effQaBy,
    };
    this.service
      .post('qa/correctivePreventiveAction.php?type=saveCapaEffectiveness', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Effectiveness check recorded. CAPA is now complete.');
          this.closeView();
          this.load();
        } else {
          alertify.error(response?.status || 'Failed to save effectiveness check');
        }
      });
  }

  getStatusClass(status: string): string {
    return statusClass(status);
  }

  getStatusLabel(status: string): string {
    return statusLabel(status);
  }

  getEmpDisplayName(): string {
    return getEmpDisplayName();
  }
}
