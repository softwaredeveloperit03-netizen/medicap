import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

 

  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getreqLog();
    }
 
   
    reqData;


  getreqLog() {
    this.service.get('store/stocktransfer.php?type=To_Security_outward').subscribe((response: any) => {
      this.reqData = response;
     });
  }

  item;
  status;
  isDIGI = false;
  emp_id;

  openDigiSign(item,value){
     this.isDIGI = true;
    this.status=value;
    this.item=item;
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
        this.saverequest(this.item,this.status)
      }
      else{
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
  

  
  saverequest(item,status) {
 
  let temp ={};
 
    this.service.post('store/stocktransfer.php?type=securtyApproval&id='+item['id']+'&status='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Outward  Successfully');
        this.getreqLog();
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  
  }

 
  
}



