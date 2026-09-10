import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-shiftmanagement',
  templateUrl: './shiftmanagement.component.html',
  styleUrls: ['./shiftmanagement.component.css']
})
export class ShiftmanagementComponent implements OnInit {
  departments;
  shifts;
  employees;
  constructor(private service: DataAccessService) {
   }

  ngOnInit() {
    this.getApprovedEmployees();
    this.getShifts();
  }

  getShifts() {
    this.service.get('hrDepartment.php?type=getShiftsByDepartment')
    .subscribe(response => {
      this.shifts = response;
    });
  }

  getApprovedEmployees() {
    this.service.get('hrDepartment.php?type=getApprovedEmployees')
    .subscribe(response => {
      this.employees = response;
    });
  }
}
