import { Component, OnInit } from '@angular/core';
import {DataAccessService} from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-checklist',
  templateUrl: './checklist.component.html',
  styleUrls: ['./checklist.component.css']
})
export class ChecklistComponent implements OnInit {
  isNew;
  results;
  selectedResult=[];
  checkList=[];
  isEdit = false;
  isView = false;
  selected_type = '';
  isChecklist = false;
  constructor(private service :DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit(): void {
this.get_rights();
    this.getCheckList();
  }
 

  addList(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.checkList[this.checkList.length] = temp;
    data.resetForm();
  } 

  del(index) {
    this.checkList.splice(index, 1);
  }

 

  getCheckList(){
    this.service.get('engineering/preventive.php?type=getPreventChecklist&checktype=calibration').subscribe(response =>{
      this.results = response;
    });
  }

  

  saveList(){
    let temp ={};
    temp['selected_type'] =  this.selected_type; 
    temp['checkList'] =this.checkList;
    this.service.post('engineering/preventive.php?type=saveChecklist&id='+this.selectedResult['id'],JSON.stringify(temp)).subscribe(response =>{
      if(response['status'] == 'success'){
        this.getCheckList();
        this.isNew = false;
        alertify.success("CheckList Inserted Successfully !!!");
      }
      else{
        alertify.error("Error to save CheckList !!!");
      }
    });
  }

  downloadchecklist() {
    this.service.open('engineering/preventive.php?type=downloadPMChecklist&selected_type=' + this.selected_type + '&id=' + this.selectedResult['id']);
  }

  view(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.isView = true;
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
