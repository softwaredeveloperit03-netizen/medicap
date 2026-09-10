import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isInit = true;
  specifications;
  selectedTesting;
  selectedSpecification;
  tests;
  isapprove = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
  }

  getPendingTesting() {
    this.service.get('qc/testing/raw.php?type=getCheckedTestingReport').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.selectedTesting = this.specifications[index];
    this.tests = this.selectedTesting['tests'];
    let flag = 0;
    this.tests.forEach(element => {
      if (element['observation'] == "fail") {
        flag = 1;
      }
    });
    if (flag == 1) {
      this.isapprove = false;
    } else {
      this.isapprove = true;
    }
    this.isInit = false;
  }

  approveTesting(status){
    this.service.get('qc/testing/raw.php?type=approveTesting&status=' + status + '&id=' + this.selectedTesting['id']).subscribe(response => {
      if (response['status']=='success') {
        alertify.success('Testing Approval Successfully');
        this.isInit = true;
        this.getPendingTesting();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  showOOSForm(index) {}

}
