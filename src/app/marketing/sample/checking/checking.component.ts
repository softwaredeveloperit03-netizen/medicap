import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  selectedResult: any = {};
  sampleList: any[] = [];
  units: any[] = [];

  issued_qty = '';
  issued_unit = '';
  qa_remarks = '';
  isSubmitting = false;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplelist();
    this.getUnits();
  }

  view(entry: any) {
    this.selectedResult = entry;
    this.issued_qty = entry.quantity_required || '';
    this.issued_unit = entry.unit || '';
    this.qa_remarks = '';
    this.isView = true;
  }

  getUnits() {
    this.service.get('common.php?type=getUnits_List').subscribe((response) => {
      this.units = Array.isArray(response) ? response : [];
    });
  }

  getSamplelist() {
    this.service.get('marketing/sample.php?type=getSamples').subscribe(response => {
      this.sampleList = Array.isArray(response) ? response : [];
    });
  }

  issueSample() {
    if (!this.issued_qty) {
      alertify.error('Please enter the issued quantity');
      return;
    }
    if (this.isSubmitting) {
      return;
    }
    this.isSubmitting = true;
    const payload = {
      id: this.selectedResult['id'],
      issued_qty: this.issued_qty,
      issued_unit: this.issued_unit,
      qa_remarks: this.qa_remarks
    };
    this.service.post('marketing/sample.php?type=issueSample', JSON.stringify(payload)).subscribe((response: any) => {
      this.isSubmitting = false;
      if (response['status'] === 'success') {
        alertify.success('Quantity issued successfully');
        this.isView = false;
        this.getSamplelist();
      } else {
        alertify.error(response['message'] || 'Failed to issue, please try again!');
      }
    }, () => {
      this.isSubmitting = false;
      alertify.error('Failed to issue, please try again!');
    });
  }

  rejectSample() {
    if (this.isSubmitting) {
      return;
    }
    this.isSubmitting = true;
    const payload = {
      id: this.selectedResult['id'],
      qa_remarks: this.qa_remarks
    };
    this.service.post('marketing/sample.php?type=rejectSample', JSON.stringify(payload)).subscribe((response: any) => {
      this.isSubmitting = false;
      if (response['status'] === 'success') {
        alertify.success('Request rejected');
        this.isView = false;
        this.getSamplelist();
      } else {
        alertify.error(response['message'] || 'Failed to reject, please try again!');
      }
    }, () => {
      this.isSubmitting = false;
      alertify.error('Failed to reject, please try again!');
    });
  }
}
