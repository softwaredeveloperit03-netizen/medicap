import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css'],
})
export class ApprovalComponent implements OnInit {
  isView = false;
  results;

  selectedResult = [];
  emp_id: string;
  isDIGI: boolean;
  status: any;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getPendingMaintenances();
  }

  getPendingMaintenances() {
    this.service
      .get('engineering/maintenance.php?type=getDeptVerifiedRecords')
      .subscribe((response) => {
        this.results = response;
      });
  }
  getMaintenanceDetails() {
    this.service
      .get(
        'engineering/maintenance.php?type=getMaintenanceDetails&maintenance_no=' +
          this.selectedResult['maintenance_no']
      )
      .subscribe((response: any) => {
        this.selectedResult = response;
      });
  }
  viewfile(link) {
    window.open(this.service.url + 'upload/maintenance/' + link);
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service
      .get(
        'engineering/maintenance.php?type=approveQARecord&status=' +
          status +
          '&id=' +
          this.selectedResult['id']
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Data updated Successfully!');
          this.isView = false;
          this.getPendingMaintenances();
        } else {
          alertify.error('Failed an error occured,please try again!');
        }
      });
  }
  openDigiSign(value) {
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = value;
  }

  loginPassward = '';
  digiSign(data) {
    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }

    this.service
      .get(
        'login.php?type=checkDigiSIgn&mpin=' +
          this.loginPassward +
          '&emp_id=' +
          this.emp_id
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Digi-Sign Verified successfully');
          this.isDIGI = false;
          this.loginPassward = '';
          this.update(this.status);
        
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }
}
