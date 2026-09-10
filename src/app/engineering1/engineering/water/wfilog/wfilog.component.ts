import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 

@Component({
  selector: 'app-wfilog',
  templateUrl: './wfilog.component.html',
  styleUrls: ['./wfilog.component.css']
})
export class WfilogComponent implements OnInit {

  constructor(private service : DataAccessService) { }

  wfiList=[]
  result:any;

  ngOnInit(): void {
    this.getWfiLog()
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  rights;


  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' + localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
    });
  }

  add(data:any)
  {
    if (!data.valid) {
      //console.log("inside add function")
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.result[this.result.length]=temp;
    data.restForm();


    //  this.wfiList.push(data.value)
    //  this.save(data)
    //  this.getWfiLog()

  }
  del(index)
  {
  this.result.splice(index,1);

  }
  getWfiLog(){
    this.service.get('engineering/watertank.php?type=getWfiLog').subscribe(response =>{
      this.result=response;

    });
  }
  save(data:any)
  {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp=data.value

   
    this.service.post('engineering/watertank.php?type=saveWfiLog',JSON.stringify (temp)).subscribe(response =>{
      if (response['status'] === 'success') {
        // console.log(this.result)
        // this.getHdpe();
        alertify.success('Record Inserted successfully');
        data.resetForm();
        this.getWfiLog()
      } else {
        alertify.error(response['status']);
      }
    });
  }

}
