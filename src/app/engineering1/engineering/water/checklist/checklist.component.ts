  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;

@Component({
  selector: 'app-checklist',
  templateUrl: './checklist.component.html',
  styleUrls: ['./checklist.component.css'],
})
export class ChecklistComponent implements OnInit {
    from_date = '';
    to_date = '';
    today = '';
    results;
    checkList=[];
    isNew=false;
    isView=false;
    selectedResults=[];
    constructor(private service: DataAccessService) {
      this.loggedInDept = localStorage.getItem('department');

    }
  
    ngOnInit(): void {
      this.getCheckListLog();
      this.get_rights();
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
     newCheckList(){
      this.isNew = true;
    }
    view(index){
      console.log("Print")
      this.selectedResults =  this.results[index];
      this.isView = true;
    }
    del(index) {
      this.checkList.splice(index, 1);
    }
  
    getCheckListLog() {
      this.service.get('checklist.php?type=getChecklist').subscribe(response => {
        this.results = response;
      });
    }
  
    download() {
      this.service.open('checklist.php?type=downloadChecklists&id='+this.selectedResults['id']);
    }
    saveChecklist(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      let temp = data.value;
      temp['checklist'] = this.checkList;
      this.service.post('checklist.php?type=saveChecklist', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getCheckListLog();
          this.isNew = false;
          data.resetForm();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
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