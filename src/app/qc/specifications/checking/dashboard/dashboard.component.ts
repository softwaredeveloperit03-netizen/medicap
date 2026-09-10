import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'master-specification-raw-checking-1', title: 'Primary Review', route: 'master/specification/raw/checking/1', icon: 'fa-eye', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-checking-2', title: 'Secondary Review', route: 'master/specification/raw/checking/2', icon: 'fa-eye', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'master-specification-raw-checking-3', title: 'QA Review', route: 'master/specification/raw/checking/3', icon: 'fa-eye', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
  ];

  plant_id:any;
  constructor(private service: DataAccessService) {
  
    this.plant_id = this.service.getPlantConfigFields('plant_id');

   }

  ngOnInit(): void {
    console.log('dsad');
    this.get_rights();
  }
  rights;
  righ;
  ischecker;
    get_rights() {
      this.service.get('hr/employee.php?type=getrights&module_name=qc&department1=specifications&form_type=user&form_name=New Quotation&user_access=Grant&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
        this.rights = response;
        this.righ=this.rights[0].isuser
        this.ischecker=this.rights[0].ischecker
        console.log(this.rights)
        console.log(this.ischecker)
        console.log(this.righ)
      });
    }
}
