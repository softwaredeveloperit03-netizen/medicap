import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {


    results
    constructor(private service: DataAccessService) {
 
    }
    ngOnInit() {    
      this.getResetRequests();
    }
  
    getResetRequests(){
      this.service.get('it/password.php?type=getResetRequests').subscribe(response=>{
        this.results=response;
      });
    }



    isDIGI= false
    status: any;
    data ;
    emp_id;
    
  openDigiSign(status,data){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.status = status;
    this.data = data;
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
        this.updateResetRequests(this.status,this.data)
 
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
 
 
    updateResetRequests(status,data){


      let temp = data;
 
        this.service.post('it/password.php?type=updatePassword&status='+status+'&id='+temp['id'], JSON.stringify(temp)).subscribe(response =>{
          if(response['status']){
            alertify.success("Password Reset Successfully !!");
            this.getResetRequests();
          }else{
            alertify.error("Failed: An error occured, please try again!");
          }
        });
    }



  
  }
  