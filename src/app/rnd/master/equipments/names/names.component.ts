import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-names',
  templateUrl: './names.component.html',
  styleUrls: ['./names.component.css'],
})
export class NamesComponent implements OnInit {
  isNew = false;
  results;
  constructor(private service: DataAccessService) { this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getEquipmentNames();
    this.get_rights();
  }
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

  getEquipmentNames() {
    this.service
      .get('qa/equipments.php?type=getEquipmentNames')
      .subscribe((response) => {
        this.results = response;
      });
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'qa/equipments.php?type=saveEquipmentName',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record Inserted Successfully');
          this.getEquipmentNames();
          this.isNew = false;
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
}
