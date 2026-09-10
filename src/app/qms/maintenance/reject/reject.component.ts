
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-reject',
  templateUrl: './reject.component.html',
  styleUrls: ['./reject.component.css']
})
export class RejectComponent implements OnInit {


  isView = false;
  results;

  selectedResult = [];
    emp_id: string;
    isDIGI=false
    status: any;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getRejectedMaintenance();
  }

  getRejectedMaintenance(){
    this.service.get('engineering/maintenance.php?type=getRejectedMaintenance').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  update(status){
    this.service.get('engineering/maintenance.php?type=checkMaintenance&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data updated Successfully!');
        this.isView = false;
        this.getRejectedMaintenance();
      }else{
        alert('Failed an error occured,please try again!');
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
