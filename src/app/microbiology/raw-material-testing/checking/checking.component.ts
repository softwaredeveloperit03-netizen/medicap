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
  selectobservation;
  isObservation = false;
  selectedTest = [];
  form_type = "error1";
  correct_result;
  isApprove = false;
  isReject = false;
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
    this.isInit = false;
  }


  // updateTesting(status){
  //   this.service.get('qc/testing/raw.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id']).subscribe(response => {
  //     if (response['status']=='success') {
  //       alertify.success('Testing Approval Successfully');
  //       this.isInit = true;
  //       this.getPendingTesting();
  //     } else {
  //       alertify.error('Failed: An error occured, please try again!');
  //     }
  //   });
  // }

  updateTesting(status){
    let temp = this.selectedTesting;
    
    //checkTesting
    temp["material_code"] = this.selectedTesting["material_code"];
    temp["batch_no"] = this.selectedTesting["batch_no"];
    temp["grn_no"] = this.selectedTesting["grn_no"];
    temp["id"] = this.selectedTesting["id"];
    this.service.post('qc/testing/raw.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id'] ,JSON.stringify(temp)).subscribe(response => {
      if (response['status']=='success') {
        alertify.success('Testing Approval Successfully');
        this.isInit = true;
        this.getPendingTesting();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
