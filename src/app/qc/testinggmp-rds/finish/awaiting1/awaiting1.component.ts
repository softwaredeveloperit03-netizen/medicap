import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
declare let alertify;
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting1',
  templateUrl: './awaiting1.component.html',
  styleUrls: ['./awaiting1.component.css']
})
export class Awaiting1Component implements OnInit {
 

  isInit = true;
  specifications;
  isView=false;
  selectedTesting = [];
  selectedSpecification;
  tests;
  isapprove = false;
  selectobservation;
  private _isObservation = false;
    emp_id: string;
    isDIGI: boolean=false
    materialForm: any;
    isbutton: boolean=true
    status: any;
  public get isObservation_1() {
    return this._isObservation;
  }
  public set isObservation_1(value) {
    this._isObservation = value;
  }
  public get isObservation() {
    return this._isObservation;
  }
  public set isObservation(value) {
    this._isObservation = value;
  }
  selectedTest = [];
  form_type = "error1";
  correct_result;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPendingTesting();
  }

  getPendingTesting() {
    this.service.get('qc/testing/raw.php?type=getPendingTestingReport&material_type=Raw Material&is_RDS='+1).subscribe(response => {
      this.specifications = response;
    });
  }

  viewSpecification(index) {

    this.selectedTesting = this.specifications[index];
    this.tests = this.selectedTesting['tests'];
    this.selectobservation = this.tests[index];
    for (let i = 0; i < this.tests.length; i++) {
      let test = this.tests[i];
      test['correct_result'] = test['result'];
      this.tests[i] = test;
    }

    let flag = 0;

    this.tests.forEach((element, index) => {
      if (element['observation'] !== "complies" && element['test_status'] !== "tested" ) {
        flag = 1;
        this.tests[index]['error_type'] = element['checker_action'];
      }else{
        if (element['test_status'] == "tested" ) {
          
          this.tests[index]['error_type'] = element['checker_action'];
        }else{
          this.tests[index]['error_type'] = 'NA';
        }
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

  saveCorrectResult(){
    this.selectedTesting['tests'] = this.tests;
    let temp=this.selectedTesting;

    this.service.post('qc/testing/raw.php?type=checkTesting&id='+this.selectedTesting['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('result save sucessfuly');
        this.isInit = true;
        this.getPendingTesting();
      } else {
        alertify.error('An error occured');
      }
    });
  }

  
  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isbutton = false;
        this.loginPassward ='';
        this.updateTesting(this.status)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }


  updateTesting(status){
    let temp={};
    temp['spec_tests'] = this.tests;

    this.service.post('qc/testing/raw.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id'],JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('Testing Updated Successfully');
        this.isbutton=true
        // this.isInit = true;
         this.router.navigate(['/qc/testing-rds/raw']);
        this.getPendingTesting();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

  getColor(testStatus: string): string {
    switch (testStatus) {
        case 'tested':
            return 'rgb(245, 64, 51)';
        case 'retested':
            return 'rgb(124, 243, 87)';
        default:
            return '';
    }
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

  view(index){
    this.isView=true;
    this.isInit = false;
    this.selectedTesting=this.specifications[index];

  }


  opedRds(file) {
    if(file==null){

      window.open(this.service.url + '../../upload/testing/rds/index.html');
    }else{

      window.open(this.service.url + '../../upload/testing/rds/' + file);
    }
     //window.open(url, '_blank');
  }
}
