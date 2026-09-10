import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-waterclrecord',
  templateUrl: './waterclrecord.component.html',
  styleUrls: ['./waterclrecord.component.css']
})
export class WaterclrecordComponent implements OnInit {
  flag2=false
    flag3=false;
    flag1=false;
    flag4=false
    isView=false
    results:any
    units;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');

   }

  ngOnInit(): void {
    this.getWaterChlorination()
    this.getUnits();
  }
  getUnits() {
    this.service.get('common.php?type=getUnits').subscribe(response => {
      this.units = response;
    });
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


  
  getWaterChlorination() {
    this.service.get('engineering/watertank.php?type=getWaterChilorinationRecords').subscribe(response => {
      this.results = response;
    })
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

  submit(data)
  {
    let temp=data.value
    this.service.post('engineering/watertank.php?type=saveWaterChlorniation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isView=false
        this.getWaterChlorination()
        // this.getWaterHardnessDetails()
      
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });


  }
  
  

}
