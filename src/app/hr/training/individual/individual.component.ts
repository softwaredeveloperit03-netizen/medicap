import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-individual',
  templateUrl: './individual.component.html',
  styleUrls: ['./individual.component.css']
})
export class IndividualComponent implements OnInit {
 
  results;
  isNewTraining = false;

  
  
  constructor(private service: DataAccessService) { 
    this.loggedInDept = localStorage.getItem('department');

  }

  ngOnInit() {
     this.getchecklist();
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


  
  getchecklist() {
    this.service.get('training.php?type=getInductionCheklist').subscribe(response => {
      this.results = response;
    });
  }

 

  department_name = '';
  subject_covered ='';

  checklistData =[];

 
  addCheckist() {

    if (this.subject_covered == '') {
      alert('All fields are required');
      return;
    }

   let temp = {}; 
   temp['department_name'] = this.department_name;
   temp['subject_covered'] = this.subject_covered;

   this.checklistData.push(temp);
   this.subject_covered='';
 
  }


  delChecklist(i){
    this.checklistData.splice(i,1);
  }
  
  
  saveChecklist(data) {

    if (this.checklistData.length == 0) {
      alert('Add Checklist!!!!!!!');
      return;
    }
 
    this.service.post('training.php?type=saveinductionChecklist', JSON.stringify(this.checklistData)).subscribe(response => {
      if (response['status'] == 'success') {
        this.checklistData = [];
        this.isNewTraining = false;
        this.getchecklist();
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }

 
  
}
