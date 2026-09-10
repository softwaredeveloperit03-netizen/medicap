import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe],
})
export class LogComponent implements OnInit {
  results: any[] = [];
  selectedRecord: any = null;
  isView = false;
  fromDate = '';
  toDate = '';
  maxDate = '';
  checkingId: number | null = null;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    const today = new Date();
    this.fromDate = this.datePipe.transform(today, 'yyyy-MM-01') || '';
    this.toDate = this.datePipe.transform(today, 'yyyy-MM-dd') || '';
    this.maxDate = this.toDate;
  }

  ngOnInit(): void {
    this.getLog();
  }

  getLog(): void {
    this.service
      .get(
        'qa/equipmentCleaningVerification.php?type=getEquipmentCleaningVerificationLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.selectedRecord = record;
    this.isView = true;
  }

  downloadLog(): void {
    this.service.open(
      'qa/equipmentCleaningVerification.php?type=downloadEquipmentCleaningVerificationLog&from_date=' +
        this.fromDate +
        '&to_date=' +
        this.toDate
    );
  }

  downloadForm(id: number): void {
    this.service.open(
      'qa/equipmentCleaningVerification.php?type=downloadEquipmentCleaningVerificationForm&id=' + id
    );
  }

  isChecked(record: any): boolean {
    return !!(record?.checked_by_date && String(record.checked_by_date).trim());
  }

  markChecked(record: any): void {
    if (!record?.id || this.isChecked(record) || this.checkingId === record.id) {
      return;
    }

    this.checkingId = record.id;
    this.service
      .post(
        'qa/equipmentCleaningVerification.php?type=updateEquipmentCleaningVerificationChecked',
        JSON.stringify({ id: record.id })
      )
      .subscribe(
        (response: any) => {
          this.checkingId = null;
          if (response?.status === 'success') {
            record.checked_by_date = response.checked_by_date;
            if (this.selectedRecord?.id === record.id) {
              this.selectedRecord.checked_by_date = response.checked_by_date;
            }
            alertify.success('Checked By & Date saved.');
          } else if (response?.status === 'already_checked') {
            record.checked_by_date = response.checked_by_date;
            if (this.selectedRecord?.id === record.id) {
              this.selectedRecord.checked_by_date = response.checked_by_date;
            }
            alertify.warning('Already checked.');
          } else {
            alertify.error(response?.status || 'Failed to save Checked By & Date');
          }
        },
        () => {
          this.checkingId = null;
          alertify.error('Failed to save Checked By & Date');
        }
      );
  }
}
