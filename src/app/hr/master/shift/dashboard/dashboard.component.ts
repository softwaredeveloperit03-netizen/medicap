import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  holidays;
  isNewHoliday = false;
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getHolidays();
    this.get_rights();
  }
  short_leave_applicable;
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//

  getHolidays() {
    this.service
      .get('hr/shift.php?type=get_shift_list')
      .subscribe((response) => {
        this.holidays = response;
      });
  }

  addShift(shift) {
    if (!shift.valid) {
      alertify.error('All fields are required');
      return;
    }

    this.service
      .post('hr/shift.php?type=save_shift', JSON.stringify(shift.value))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          shift.reset();
          this.getHolidays();
          this.isNewHoliday = false;
          alertify.success('save successfully');
        } else {
          alertify.error(response['status']);
        }
      });
  }

  // addShift(shift) {

  //   this.service.post('hr/shift.php?type=save_shift', JSON.stringify(shift.value))
  //   .subscribe(response => {
  //     if(response['status']=='success'){
  //       shift.reset();
  //       this.getHolidays();
  //       this.isNewHoliday = false;
  //       alertify.success("save successfully")
  //     }else{
  //       alertify.error(response['status'])
  //     }
  //     },
  //   (error: Response) => {
  //     if (error.status === 400) {
  //       alertify.error('An error has occurred.');
  //     } else {
  //       alertify.error('An error has occurred, http status:' + error.status);
  //     }
  //   });
  // }
}
