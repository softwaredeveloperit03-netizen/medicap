import { HttpClient } from '@angular/common/http';
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
  results;

  selectedResult = [];
    emp_id: string;
    isDIGI =false
    status: any;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getpendinginitiation();
  }

  getpendinginitiation() {
    this.service.get('sops.php?type=getPendingSOPs').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status) {
    this.service.get('sops.php?type=checkCreatedSOP&status=' + status + '&sop_no=' + this.selectedResult['sop_no']).subscribe(response => {
      if (response['status'] == 'success') {
       alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getpendinginitiation();
      } else {
       alertify.error('Failed: AN error occured');
      }
    });
  }

  
 openDigiSign(value){
  this.emp_id = localStorage.getItem('emp_id');
  console.log(this.emp_id)
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
      this.loginPassward ='';
      this.update(this.status)


    
      
    }
    else
    {
      alertify.error('Digi-Sign Not Verified');

    }
  });
}


}
