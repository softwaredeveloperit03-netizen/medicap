import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-vehicle-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css'],
})
export class RegisterComponent implements OnInit {
  results: any;
  isuser = 'No';
  rights: any;
  loggedInDept: string | null = null;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.getVehiclManagementlog();
    this.get_rights();
  }

  getVehiclManagementlog(): void {
    this.service.get('admin/vehicle_managment.php?type=getVehiclManagementlog').subscribe((response: any) => {
      this.results = response;
    });
  }

  get_rights(): void {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        if (Array.isArray(response) && response[0]) {
          this.isuser = response[0].isuser || 'No';
        }
      });
  }
}
