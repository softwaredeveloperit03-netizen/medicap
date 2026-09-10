import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-requesition',
  templateUrl: './requesition.component.html',
  styleUrls: ['./requesition.component.css']
})
export class RequesitionComponent implements OnInit {

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
   }

  ngOnInit(): void {
    this.getDepartments();
    this.getDetails();
    this.get_rights();
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

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
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


isNew=false
new()
{
 this.isNew=true

}

departments;
getDepartments(){
  this.service.get('qa/all2.php?type=getDepartments').subscribe((response:any) => {
    this.departments = response;
   
  });
}

data;
getDetails()
{
  this.service.get('qa/all2.php?type=getfirst_aid_material').subscribe((response:any) => {
    this.data = response;
   
  });
}

save(data) {
  console.log(data.value);
  if (!data.valid) {
    alert('All fields are required');
    return;
  }

  this.service.post('qa/all2.php?type=save_requesition', JSON.stringify(data.value)).subscribe(response => {
    if (response['status'] == 'success') {
      alert('Saved Successfully');
      // this.router.navigate(['/checklist']);
    } else {
      console.log(response);
      alert('Failed: An error occured, please try again!');
    }
  });
}

}
