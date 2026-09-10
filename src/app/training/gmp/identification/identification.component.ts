import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-identification',
  templateUrl: './identification.component.html',
  styleUrls: ['./identification.component.css']
})
export class IdentificationComponent implements OnInit {

  constructor(private service: DataAccessService) {
  }
 
  training_category = 'cGMP Training';
  subject = ' ';
  

 isOther = false;
 employees;

 trainings;

 isView = false;

 ngOnInit() {
   this.getTrainingCategory();
   this.get_rights();
   this.getsubject();
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
  department_name = localStorage.getItem('department');

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

 

 category_data;

 getTrainingCategory() {
   this.service.get('training.php?type=getTrainingCategory').subscribe((response) => {
       this.category_data = response;
     });
 }

 getTrainingNeeds() {
   this.service.get('training.php?type=getOJTTraining').subscribe((response) => {
       this.trainings = response;
     });
 }




 getEmployees(level) {
   this.service.get('training.php?type=getEmployeeByLevel&deptmt101=' + localStorage.getItem('department')+'&level='+level).subscribe((response) => {
       this.employees = response;
     });
 }



 

  
 saveTrainingNeeds(data) {
   if (!data.valid) {
     alert('All fields are required');
     return;
   }


   const selectedItems = this.employees.filter((term) => term.check);
 
   if (selectedItems.length === 0) {
     alert('No items selected');
     return;
   }
 
   let temp  = data.value;

   temp['employees'] = selectedItems;
   temp['frequency'] = selectedItems[0]?.frequency;
   temp['training_category'] = this.training_category;

   this.service.post('training.php?type=generateGmpSchedule', JSON.stringify(temp)).subscribe((response) => {
       if (response['status'] == 'success') {
         alert('Training has been allocated to employee');
         this.getTrainingNeeds();
         data.reset();
         this.employees =[];
       } else {
         alert('An error occured');
       }
     });
 }

 add_subject;
 reference_document = '';
 checkOther(value) {
   if (value == 'Add New') {
     this.add_subject = true;
   } 
 }

 subject_list;
 getsubject() {
   this.service.get('training.php?type=getsubject1&training_category=' +this.training_category).subscribe((response) => {
       this.subject_list = response;
     });
 }

 subject_name;
 add_subject_name(data) {
   if (!data.valid) {
     alertify.error('All fields are required');
     return;
   }

   let temp = data.value;
   this.service.post('training.php?type=addsubjects', JSON.stringify(temp)).subscribe((response) => {
       if (response['status'] == 'success') {
         this.getsubject();
         this.add_subject = false;
         data.reset();

         alertify.success('Subject saved successfully');
       } else {
         alertify.error(response['status']);
       }
     });
 }

 
  


 saveOJT(data) {

   if (!data.valid) {
     alert('All fields are required');
     return;
   }

   let temp = data.value;
    this.service.post('training.php?type=saveOJT',JSON.stringify(temp))
   .subscribe(response => {
     if (response['status'] == 'success') {
       data.reset();
         alert('successfully saved');
     } else {
       alert('An error occured');
     }
   });
 }





}
