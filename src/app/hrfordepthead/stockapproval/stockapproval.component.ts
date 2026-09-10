import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-stockapproval',
  templateUrl: './stockapproval.component.html',
  styleUrls: ['./stockapproval.component.css']
})
export class StockapprovalComponent implements OnInit {

  materials: any;
 
    
  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getAllMaterial();
    }
 

  getAllMaterial() {
    this.service.get('store/stocktransfer.php?type=getMaterialForApprovalStock').subscribe((response: any) => {
      this.materials = response;
     });
  }


   
  status;
  item;
  isDIGI = false;
  emp_id;

 

  openDigiSign(item,status){
    this.status = status;
    this.item = item;
     this.isDIGI = true;
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
    let temp = {};
    this.service.post('store/stocktransfer.php?type=ApproveMaterialStock&id='+item['id']+'&status='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getAllMaterial();
         alertify.success('Successful!!!!!');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
 
}
