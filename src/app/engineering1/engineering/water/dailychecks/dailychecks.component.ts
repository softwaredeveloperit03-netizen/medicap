import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dailychecks',
  templateUrl: './dailychecks.component.html',
  styleUrls: ['./dailychecks.component.css']
})
export class DailychecksComponent implements OnInit {
  isView=false
    results: Object;
    flag1=false
    flag2=false
    flag3=false
    flag4=false
    flag5=false
  constructor(private service: DataAccessService, private router: Router) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
    this.getDailyChecks()
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

  getDailyChecks() {
    this.service.get('engineering/watertank.php?type=getDailyChecksData').subscribe(response => {
      this.results = response;
    })
  }

  submit(data)
  {
    let temp=data.value
    this.service.post('engineering/watertank.php?type=saveDailyChecksDetails', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isView=false
        this.getDailyChecks()
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }

  onValueChange1(newValue: string) {
    const regex = /^[0-9]+$/;     
    const isValidRegex = regex.test(newValue);
  
    if (isValidRegex) {
  
      this.flag1=false
    } else {
  
  
      this.flag1=true
      if(newValue==''|| null)
        {
          this.flag1=false
        }
    }
    if(this.flag1==true)
      {
        this.flag3=true
  
      }
      if(this.flag1==false)
        {
        this.flag3=false
        }
  
  }

  onValueChange2(newValue: string) {
    const regex = /^[0-9]+$/;     
    const isValidRegex = regex.test(newValue);
  
    if (isValidRegex) {
  
      this.flag2=false
    } else {
  
  
      this.flag2=true
      if(newValue==''|| null)
        {
          this.flag2=false
        }
    }
    if(this.flag2==true)
      {
        this.flag3=true
  
      }
      if(this.flag2==false)
        {
        this.flag3=false
        }
  
  }

  onValueChange3(newValue: string) {
    const regex = /^[0-9]+$/;     
    const isValidRegex = regex.test(newValue);
  
    if (isValidRegex) {
  
      this.flag4=false
    } else {
  
  
      this.flag4=true
      if(newValue==''|| null)
        {
          this.flag4=false
        }
    }
    if(this.flag4==true)
      {
        this.flag3=true
  
      }
      if(this.flag4==false)
        {
        this.flag3=false
        }
  
  }


  onValueChange4(newValue: string) {
    const regex = /^[0-9]+$/;     
    const isValidRegex = regex.test(newValue);
  
    if (isValidRegex) {
  
      this.flag5=false
    } else {
  
  
      this.flag5=true
      if(newValue==''|| null)
        {
          this.flag5=false
        }
    }
    if(this.flag5==true)
      {
        this.flag3=true
  
      }
      if(this.flag5==false)
        {
        this.flag3=false
        }
  
  }

}
