import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  flag = 0;
  equipments;
  departments;
  
  equipment_type = '';
  department = '';
  section = '';
  sections;

  isView = false;
  result = [];
  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.getDepartments();
    this.route.paramMap.subscribe(params => {
      this.equipment_type = params.get('id1');
      this.department = params.get('department');
      this.section = params.get('section');
      this.getEquipmentNames();
    });
  }

  getDetails() {
    if (this.flag >= 2) {
      this.route.paramMap.subscribe(params => {
        this.getEquipmentDetails(params.get('id'));
      });
    }
  }

  getEquipmentNames() {
    this.service.get('qa/equipments.php?type=getEquipmentNames&equipment_type=' + this.equipment_type).subscribe(response => {
      this.equipments = response;
      this.flag++;
      this.getDetails();
    });
  }

  getDepartments() {
    this.service.get('qa/equipments.php?type=getDepartments').subscribe(response => {
      this.departments = response;
      for (let i  = 0; i < this.departments.length; i++) {
        let department = this.departments[i];
        if (department['department_name'] == this.department) {
          this.sections = department['sections'];;
        }
      }
      this.flag++;
      this.getDetails();
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
    temp['id'] = this.result['id'];
    this.service.post('qa/equipments.php?type=editEquipment', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.router.navigate(['/equipments']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  getEquipmentDetails(id) {
    this.service.get('qa/equipments.php?type=getEquipmentDetails&id=' + id).subscribe((response: any) => {
      this.result = response;
      this.isView = true;
    });
  }

}
