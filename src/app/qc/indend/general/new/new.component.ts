import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  vendors;
  units;
  clients;
  isClients = false;

  materialList = [];
  selectedMaterial = [];
  selectedVendor=[];
  materials;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getUnits();
    this.getVendors();
  }

  getGeneralMaterials(value) {
    this.service.get('common.php?type=getGeneralMaterials&material_type=' + value).subscribe(response => {
      this.materials = response;
    });
  }

  
  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }

  checkRequiredfor(value) {
    if (value !== 'Own') {
      this.getClients();
      this.isClients = true;
    } else {
      this.isClients = false;
    }
  }

  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }
  selectMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.materials[index];
    } else {
      this.selectedMaterial = [];
    }
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['grade'] = this.selectedMaterial['grade'];
    temp['vendor_name'] = this.selectedVendor['vendor_name'];
    this.materialList[this.materialList.length] = temp;
    data.resetForm();
    this.isClients = false;
  }

  del(index) {
    this.materialList.splice(index, 1);
  }

  save() {
    if (this.materialList.length == 0) {
      alertify.error('At least 1 material required in list');
      return;
    }
    this.service.post('purchase/indend/general.php?type=saveIndend', JSON.stringify(this.materialList)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.materialList = [];
        this.router.navigate(['/qc/indend/general']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  selectVendor(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedVendor = this.vendors[index];
    } else {
      this.selectedVendor= [];
    }
  }

}
