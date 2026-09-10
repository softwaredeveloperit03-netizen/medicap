import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  vendors;
  packings;
  units;
  clients;

  isClient = false;
  materialList = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
    this.getUnits();
  }

  getVendors(){
    this.service.get('store/packing.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getMaterials(value){
    this.service.get('store/packing.php?type=getMaterials&material_type=' + value).subscribe((response:any) => {
      this.packings = response;
    });
  }
  getUnits() {
    this.service.get('store/packing.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  getClients(name) {
    if (name == 'Loan License' || name == 'Third Party') {
      this.service.get('store/packing.php?type=getClients&required_for=' + name).subscribe((response:any) => {
        this.clients = response;
      });
      this.isClient = true;
    } else {
      this.isClient = false;
    }
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.materialList[this.materialList.length] = data.value;
    data.resetForm();
    this.isClient = false;
  }

  del(index) {
    this.materialList.splice(index, 1);
  }

  saveChallan(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.materialList.length == 0) {
      alertify.error('Materials are required');
      return;
    }
    let temp = data.value;
    temp['materials'] = this.materialList;
    this.service.post('store/packing.php?type=saveChallan', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record saved successfully');
        data.resetForm();
        this.materialList = [];
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
