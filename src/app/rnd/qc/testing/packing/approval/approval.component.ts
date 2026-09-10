import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

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
    this.service.get('qcDepartment.php?type=getCheckedTestingReport').subscribe(response => {
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

  saveTesting(status) {
    this.service.get('qcDepartment.php?type=updateCheckedTesting&testing_no=' + this.selectedTesting['testing_no'] + '&status=' + status).subscribe(response => {
      if (response['status'] === 'success') {
        this.getPendingTesting();
        this.isInit = true;
      }
    });
  }


}
