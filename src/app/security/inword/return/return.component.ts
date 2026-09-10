import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-return',
  templateUrl: './return.component.html',
  styleUrls: ['./return.component.css'],
  providers:[DatePipe]
})
export class ReturnComponent implements OnInit {

  isNewOutword = false;
  materials;
  isPickup = false;
  isVehicle = false;
  from_date='';
  to_date='';
  today;

  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getReturnableList();
    this.service.observableDepartment
  }

  getReturnableList() {
    this.service.get('security/inward.php?type=getReturnableList').subscribe(response => {
      this.materials = response;
    });
  }

  saveMaterialOut(materialForm) {
    if (!materialForm.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('security.php?type=saveMaterialOutForm', JSON.stringify(materialForm.value))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isNewOutword = false;
        materialForm.resetForm();
        this.getReturnableList();
        alert("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error occured');
      }
    });
  }

  getTransportVal(val) {
    if (val === 'By Transport') {
      this.isPickup = false;
      this.isVehicle = true;
    } else if (val === 'By Courier') {
      this.isVehicle = false;
      this.isPickup = true;
    }
  }


  verify(id){
    this.service.get('security/materialoutward.php?type=UpdateOutwordLog&id='+id).subscribe(Response=>{
      if(Response['status']=='success'){
        alertify.success("Update Successfully");
      }else{
        alertify.error("Failed:an error occured!")
      }
    });
  }

}
