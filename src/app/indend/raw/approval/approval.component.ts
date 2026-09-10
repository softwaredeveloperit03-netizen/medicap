import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  results;
  selectedResult: [];
    emp_id: string;
    isDIGI=false
    status: any;
    Status: any;
    Id: any;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends();
  }

  getPendingIndends() {
    this.service.get('purchase/indend/raw.php?type=getPendingIndends').subscribe(response => {
      this.results = response;
    });
  }

  updateIndend(status,id){
    this.service.get('purchase/indend/raw.php?type=updateIndend&status=' + status + '&id=' + id).subscribe(response => {
      if (response['status']) {
        alertify.success('indend Updated Successfully');
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  openDigiSign(status,id){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.Status=status
    this.Id=id
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
        this.updateIndend(this.Status,this.Id)

        
      
        
      }
      else
      {
        alertify.error('Digi-Sign Not Verified');

      }
    });
  }
  


 


}
