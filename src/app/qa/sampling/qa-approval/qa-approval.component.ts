import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-qa-approval',
  templateUrl: './qa-approval.component.html',
  styleUrls: ['./qa-approval.component.css'],
})
export class QaApprovalComponent implements OnInit {
  results;
  selectedResult = [];
  isView = false;
  release_status = 'Approved';
    emp_id: string;
    isDIGI: boolean;
    status: any;
  constructor(private service: DataAccessService) {}

  ngOnInit() {
    this.getInprocessTestings();
  }

  getInprocessTestings() {
    this.service
      .get('ipqc/finish.php?type=getPendingCOAApproval')
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  // approveTesting(status){
  //   this.service.get('ipqc/finish.php?type=saveCOAApproval&id='+this.selectedResult['id']+'&status='+ status).subscribe(response=>{
  //     if(response['status']=='success'){
  //       alertify.success('data Successfuly updated');
  //       this.isView=false;
  //       this.getInprocessTestings();
  //     }else{
  //       alertify.error('Some error occured!');
  //     }
  //   });
  // }
  approveTesting(value) {
    this.service
      .post(
        'ipqc/finish.php?type=saveCOAApproval&id=' +
          this.selectedResult['id'] +
          '&status=' +
          this.release_status +
          '&specification_no=' +
          this.selectedResult['specification_no'] +
          '&release_status=' +
          this.selectedResult['release_status'] +
          '&observation=' +
          this.selectedResult['observation'],
        JSON.stringify(this.selectedResult['tests'])
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('data Successfuly updated');
          this.isView = false;
          this.getInprocessTestings();
        } else {
          alertify.error('Some error occured!');
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
          this.approveTesting(this.status);
        } else {
          alertify.error('Digi-Sign Not Verified');
        }
      });
  }
}
  