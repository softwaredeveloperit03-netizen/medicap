import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-uvcabinate',
  templateUrl: './uvcabinate.component.html',
  styleUrls: ['./uvcabinate.component.css']
})
export class UvcabinateComponent implements OnInit {
  isView=false
    results: Object;
  constructor(private service: DataAccessService, private router: Router) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.get_rights();
  this.getCleaningRecord();
  }

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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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



    
  getCleaningRecord() {
    this.service.get('engineering/watertank.php?type=getWaterHardnessRecords').subscribe(response => {
      this.results = response;
    })
  }

  submit(data)
  {
    let temp=data.value
    this.service.post('engineering/watertank.php?type=saveCleaningRecord', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isView=false
        this.getCleaningRecord()
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

}
}
