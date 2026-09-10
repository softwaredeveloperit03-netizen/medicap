import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe]
})
export class NewComponent implements OnInit {
 
  equipment_category = 'Existing';
  preventive_maintenance = 'Applicable';
  calibration_required = 'Not Applicable';
  reqv_required = 'Not Applicable';
  equipment_type = '';
  capacity_applicable = '';
  today = '';

  constructor(private service: DataAccessService, private router: Router, private datePipe: DatePipe) {
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getDepartments();
    this.getUnit();
    this.getequipment_type();

  }
  

  units;
  getUnit() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
   

  departments: any[] = [];
  /** Only these names appear in the department dropdown; sections still load from API by name. */
  readonly allowedDepartmentNames = [
    'Production',
    'Quality Control',
    'Quality Assurance',
    'Engineering',
    'Product Development',
  ] as const;
  department = '';
  location = '';

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  sections: any[] = [];

  /** Match selected department name to API row and load its sections. */
  getSectionsByDept() {
    this.sections = [];
    this.location = '';
    if (!this.department || !Array.isArray(this.departments)) {
      return;
    }
    const dept = this.departments.find(
      (d) => d && String(d.department_name) === String(this.department)
    );
    this.sections =
      dept && Array.isArray(dept.sections) ? dept.sections : [];
  }

  
  equipment_type_data;
  getequipment_type() {
    this.service.get('master/equipment.php?type=getequipment_type_data').subscribe(response => {
      this.equipment_type_data = response;
    })
  }
 
  saveEquipment(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('master/equipment.php?type=saveEquipment', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/master/equipments']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 
  
  isEquipment =false;

  addNewWquipType(value){
    if(value == 'ADD NEW'){
      this.isEquipment = true;
    }
  }

  saveEquipmentType(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp =data.value;
    this.service.post('master/equipment.php?type=save_equipment_type', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
          alertify.success('Type Saved Successfully');
         this.isEquipment = false;
         this.getequipment_type();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
