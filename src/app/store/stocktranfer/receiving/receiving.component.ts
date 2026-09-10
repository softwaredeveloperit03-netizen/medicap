import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-receiving',
  templateUrl: './receiving.component.html',
  styleUrls: ['./receiving.component.css']
})
export class ReceivingComponent implements OnInit {

 
  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getreqLog();
    }
 
   
    reqData;


  getreqLog() {
    this.service.get('store/stocktransfer.php?type=For_Receiving').subscribe((response: any) => {
      this.reqData = response;
     });
  }

  getmaterials(material_type) {
    this.service.get('store/stocktransfer.php?type=getMaterials&material_type='+material_type).subscribe((response: any) => {
      this.materials = response;
     });
  }

  materials;
   status;
  isDIGI = false;
  emp_id;

  openDigiSign(data,status){

    if (!data.valid) {
      alert('Please Select Material !!!!!!');
      return;
    }
     this.isDIGI = true;
    this.status=status;
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
   
  selectedMaterial =[];
  material_code='';

  isselectedmat(i){
    this.selectedMaterial = this.reqData[i];
    this.getmaterials(this.selectedMaterial['material_type']) ;
    this.isNew = true;
  }
 
  isNew = false;

  saverequest(status) {
 
  let temp ={};
  temp['arData'] = this.selectedMaterial['arData'];
  temp['material_code'] = this.material_code;
 
    this.service.post('store/stocktransfer.php?type=receivedtransferMaterial&id='+this.selectedMaterial['id']+'&status='+status, JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Request Send Successfully');
        this.getreqLog();
        this.isNew = false;
        this.material_code ='';
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  
  }
 
}



