import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-paint-wall',
  templateUrl: './paint-wall.component.html',
  styleUrls: ['./paint-wall.component.css']
})
export class PaintWallComponent implements OnInit {

  
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');

   }
  
  ngOnInit(): void {
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
proper_verify=false
water_check=false
inspect_walls=false
clean_shutters=false
cracks_check=false
inspect_repairs=false
 
new()
{
 this.isNew=true
}

data;
getDetails()
{
  this.service.get('admin/facility.php?type=get_paint_wall').subscribe((response:any) => {
    this.data = response;
   
  });
}
selectedFile2:File;
onFileChanged3(event) {
  this.selectedFile2 = event.target.files[0];
}

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile2 !== undefined) {
      uploadData.append('attachment', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('admin/facility.php?type=save_paint_wall', uploadData).subscribe(response => {
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

