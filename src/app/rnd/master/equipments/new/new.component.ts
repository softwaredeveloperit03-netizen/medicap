import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  equipments;
  departments;
  
  sections;

  equipment_type = '';
  units;
  unit='';
  capacity = 0;
  calibration = 'No';

  system_calculated = 'Yes';
  weights = [];
  split_into = 5;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.checkWeights();
    this.getUnit();
  }
  getUnit() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
  }
  getEquipmentNames() {
    this.service.get('qa/equipments.php?type=getEquipmentNames&equipment_type=' + this.equipment_type).subscribe(response => {
      this.equipments = response;
    });
  }

  getDepartments() {
    this.service.get('qa/equipments.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getSections(index) {
    index = index - 1;
    if (index !== -1) {
      let temp = this.departments[index];
      this.sections = temp['sections'];
    }
  }

  saveEquipment(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['weights'] = this.weights;
    this.service.post('qa/equipments.php?type=saveEquipment', JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success') {
        this.router.navigate(['/master/equipments/log'])
        alertify.success('Record Inserted Successfully');
        this.getDepartments();
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checkWeights() {
    this.weights = [];
    for (let i = 0; i < this.split_into; i++) {
      let weight = {};
      weight["standard_weight"] = "";
      weight["limit_from"] = "";
      weight["limit_to"] = "";
      this.weights[this.weights.length] = weight;
    }
  }

}
