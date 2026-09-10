import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isInit = true;
  specifications;
  selectedTesting = [];
  selectedSpecification;
  tests;
  isapprove = false;

  isObservation = false;
  selectedTest = [];
  form_type = "error1";
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingTesting();
  }

  getPendingTesting() {
    this.service.get('qc/testing/raw.php?type=getPendingTestingReport').subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {
    this.selectedTesting = this.specifications[index];
    this.tests = this.selectedTesting['tests'];
    let flag = 0;
    this.tests.forEach(element => {
      if (element['observation'] == "fail" && element['fail_status'] !== "done") {
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

  saveTesting(data) {
    this.service.get('qcDepartment.php?type=updatePendingTesting&testing_no=' + this.selectedTesting['testing_no']).subscribe(response => {
      if (response['status'] === 'success') {
        this.getPendingTesting();
        this.isInit = true;
      }
    });
  }

  showOOSForm(index) {
    this.selectedTest = this.tests[index];
    this.isObservation = true;
  }

  updateTesting(status){
    this.service.get('qc/testing/raw.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('Testing Updated Successfully');
        this.isInit = true;
        this.getPendingTesting();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  submitOOS(data) {
    data = data.value;
    data["test_no"] = this.selectedTest["id"];
    data["observation"] = this.selectedTest["observation"];
    data["testing_no"] = this.selectedTest["testng_no"];
    data["form_type"] = this.form_type;

    this.service.post('qcDepartment.php?type=saveOOS', JSON.stringify(data)).subscribe(response => {
      if (response['status'] == "success") {
        this.isObservation = false;
        this.isInit = true;
        alertify.success(this.form_type + ' send for Approval.');
        this.getPendingTesting();
      } else {
        alertify.error('An error occured');
      }
    });
  }

}
