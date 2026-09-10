import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
 
  isNewOutword = false;
  materials;
  isPickup = false;
  isVehicle = false;
  material_type='';
  material_subtype='';
  units;
  results;
  from_date = '';
  to_date = '';
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getMaterialOutDetails();
    this.getUnits();
  }

  getMaterialOutDetails() {
    this.service.get('store/outword.php?type=getOutwordLog&from_date=' + this.from_date + '&to_date=' + this.to_date)
    .subscribe(response => {
   this.results = response;
    });
  }
  
  download() {
    this.service.open('store/outword.php?type=downloadOutwordLog&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  getMaterials() {
    this.service.get('common.php?type=getMaterialsByType&material_type=' + this.material_type + '&material_subtype=' + this.material_subtype)
    .subscribe(response => {
      this.materials = response;
    });
  }
  getUnits() {
    this.service.get('common.php?type=getUnits')
    .subscribe(response => {
      this.units = response;
    });
  }
  saveMaterialOut(materialForm) {
    if (!materialForm.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('store/outword.php?type=saveMaterialOutForm', JSON.stringify(materialForm.value))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isNewOutword = false;
        materialForm.resetForm();
        this.getMaterialOutDetails();
        alertify.success("save successfully");
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

}
