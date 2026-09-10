import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  from_rec = '';

 
  isView = false;
  isTest = false;
  specifications;
  tests;

  selectedResult = [];
  selectedTesting = [];
    emp_id: string;
    isDIGI: boolean=false
    status: any;
    isbutton: boolean=true
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    // this.getInprocessTestings();
  }
  getData(value){
    if(value=='Production'){
      this.getInprocessTestingsProd();
    }
    else if(value=='Packing'){
      this.getInprocessTestings();
    }
  }

  getInprocessTestings(){
    this.service.get('production/technical.php?type=getInprocessTestings').subscribe(response => {
      this.specifications = response;
    });
  }
  getInprocessTestingsProd(){
    this.service.get('production/technical.php?type=getInprocessTestingsProd').subscribe(response => {
      this.specifications = response;
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

 
  view(index){
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

    this.isView = true;
  }
  isapprove = false;
  selectobservation;
 

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

    this.service.post('production/technical.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id']
      + '&ti_no=' + this.selectedTesting['ti_no'],JSON.stringify(temp)).subscribe(response => {
      if (response['status']) {
        alertify.success('Testing Updated Successfully');
        this.isbutton=true
         this.isView = false;
        //  this.getInprocessTestings();
        this.specifications=[];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

}
