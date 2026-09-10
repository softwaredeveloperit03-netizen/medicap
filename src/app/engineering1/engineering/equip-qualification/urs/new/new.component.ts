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
  equipments = [];
  selectedEquipment: any = null;
  capacity = '';

  specs = [];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
    this.getEquipments();
  }

  getVendors() {
    this.service.get('qa.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }

  getEquipments() {
    this.service.get('master/equipment.php?type=getEquipments').subscribe(response => {
      this.equipments = Array.isArray(response) ? response : [];
    });
  }

  onEquipmentChange(equipmentName: string) {
    this.selectedEquipment = (this.equipments || []).find(
      (eq) => eq && eq.equipment_name === equipmentName
    ) || null;
    if (this.selectedEquipment && this.selectedEquipment.capacity) {
      this.capacity = this.selectedEquipment.capacity;
    }
  }

  addSpec(data) {
    this.specs[this.specs.length] = data.value;
    data.resetForm();
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['specs'] = this.specs;
    this.service.post('qa/qualification.php?type=saveUserRequirementSpecification', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('User requirement specification has been saved successfully');
        this.specs = [];
        this.capacity = '';
        this.selectedEquipment = null;
        data.resetForm();
      } else {
        alertify.error('An error occured, please try again!');
      }
    });
  }

}
