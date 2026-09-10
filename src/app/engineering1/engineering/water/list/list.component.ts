import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ListComponent implements OnInit {
  type='';
  results: any = [];
  selectedResults=[];
  isEdit = false;
  isDelete = false;
  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }
  isView = false;
  ngOnInit(): void {
    this.getTanks();
        this.get_rights();
  }



  getTanks() {
    this.service.get('engineering/watertank.php?type=getTanks').subscribe(response => {
      this.results = response;
    })
  }

  download() {
    this.service.open('engineering/watertank.php?type=downloadTanks')
  }

  editTankMaster(index){
    this.selectedResults = this.results[index];
    this.isEdit = true;
  }
  deleteTankMaster(index){
    this.selectedResults = this.results[index];
    this.isDelete = true;
  }
  save(data) {
    if(data.valid) 
      this.service.post('engineering/watertank.php?type=saveTankMaster', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] == 'success') {
        this.isView = false;
          alertify.success("submitted sucessfully")
          data.reset();
          this.getTanks();
        }
        else {
          alertify.error('error occured please try again')
        }
      });
    else {
      alertify.error("not valid")
    }
  }
  update(data){
    if(!data.valid){
      alertify.error("All fields are required !!!");
      return;
    }
    let temp = data.value;
      temp['tank_id'] = this.selectedResults['tank_id'];
    this.service.post('engineering/watertank.php?type=updateTank', JSON.stringify(data.value)).subscribe(response =>{
      if (response['status'] == 'success') {
          this.getTanks();
          this.isEdit = false;
          data.resetForm();
          alertify.success("Record Update Successfully !!!");
        }
        else{
          alertify.error("Error to update record !!!");
        }
    });
  }

  delete(){
    this.service.get('engineering/watertank.php?type=deleteTankMaster&tank_id='+this.selectedResults['tank_id']).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getTanks();
        this.isDelete = false;
        alertify.success("Record Inactive Successfully !!!");
      }
      else{
        alertify.error("Error to Inactive Record !!");
      }
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


}
