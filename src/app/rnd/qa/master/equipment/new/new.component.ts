import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';
declare let alertify;

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
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  getEquipmentNames() {
    this.service.get('qa/equipments.php?type=getEquipmentNames&equipment_type=' + this.equipment_type).subscribe(response => {
      this.equipments = response; 
    });
  }
  

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
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
    this.service.post('rnd/qa/master/equipment.php?type=saveEquipment', JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success') {
        this.router.navigate(['/rnd/qa/master/equipment']);
        alertify.success('Record Inserted Successfully');
        this.getDepartments();
        data.resetForm();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
