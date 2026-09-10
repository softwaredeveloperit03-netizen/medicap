import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-team',
  templateUrl: './team.component.html',
  styleUrls: ['./team.component.css']
})
export class TeamComponent implements OnInit {

  isNew = false;
  departments;
  employee;
  entries;
  entries1;
  plans;
  selectedPid;
  selectedEntry = [];
  Members = [];
  list;
  department_name = '';
  assigned_department = '';
  auditors_name = '';
  p_id = '';
  plant_id:any;
  constructor(private service: DataAccessService , private router: Router) {

    this.plant_id = this.service.getPlantConfigFields('plant_id');

   }
   getExternalTrainers;
  ngOnInit() {
    this.getDepartments();
    this.getInspectionPlan();
    this.trainer();
  }

   getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
   trainer() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.getExternalTrainers = response;
    });
  }

  getEmployee() {
    this.employee = undefined;
    this.service.get('employee.php?type=getEmployeesbyDpt&selecteddepartment=' + this.department_name).subscribe(response => {
      this.employee = response;
    });
  }

  saveForm(inspectionteam) {
    const data = inspectionteam.value;
    data['p_id'] = this.selectedPid;
    this.service.post('audit-trails.php?type=saveInspectionTeam', JSON.stringify(data))
    .subscribe(response => {


      if (response['status'] == 'success') {
        alert('Saved Successfully');
        inspectionteam.resetForm();
        this.isNew = false;
        this.getInspectionTeam(this.p_id);
      } else {
        alert('Please try Again');
      }


      
    
    });
  }
  getInspectionPlan() {
    this.service.get('audit-trails.php?type=getInspectionPlan').subscribe(response => {
      this.entries = response;
    });
  }

  getInspectionTeam(p_id) {
    this.entries1 = [];
    this.service.get('audit-trails.php?type=getInspectionTeam&p_id=' + p_id).subscribe(response => {
      this.entries1 = response;
    });
  }

  onAddTeam(selected) {
    this.isNew = true;
    this.selectedPid = selected.p_id;
    this.getInspectionTeam(selected.p_id);
  }

  // close() {
  //   this.router.navigate(['/audit']);
  // }

}
