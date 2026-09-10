import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-newreq',
  templateUrl: './newreq.component.html',
  styleUrls: ['./newreq.component.css']
})
export class NewreqComponent implements OnInit {

  
  materials: any;
 
  isNew = false;
  selectedMaterial =[];
  
 

  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getAllMaterial();
    }
 

  getAllMaterial() {
    this.service.get('store/stocktransfer.php?type=For_QA_Approval').subscribe((response: any) => {
      this.materials = response;
     });
  }


    
  matData;
   isselectedmat(i){
     this.selectedMaterial = this.materials[i];
     this.isNew = true;
   }

  status;
  isDIGI = false;
  emp_id;

 

  openDigiSign(status){
    this.status = status;
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
        this.saverequest(this.status)
      }
      else{
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
   
 
  saverequest(status) {
    let temp = {};
    this.service.post('store/stocktransfer.php?type=approveFromQA&id='+this.selectedMaterial['id']+'&status='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getAllMaterial();
        this.isNew = false;
        alertify.success('Successfully Approved From QA!!!!!');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

 
  
}
