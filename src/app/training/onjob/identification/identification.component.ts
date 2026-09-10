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
   departments;

   training_category = 'Level 3 ( On The Job Training )';
   subject = 'On The Job Training';
   

  isNewTraining = false;
  isOther = false;
  employees;
  trainers;
  emp = '';
  employeeList = [];
  selectedEmp = [];
  trainings;

  selectedNeed = [];
  isView = false;
 
  ngOnInit() {
    this.getEmployees();
    this.getTrainers();
    this.getTrainingNeeds();
    // this.getsubject();
    this.getDepartments();
    this.get_rights();
    this.getTrainingCategory();
   }
  category_data;

  getTrainingCategory() {
    this.service
      .get('training.php?type=getTrainingCategory')
      .subscribe((response) => {
        this.category_data = response;
      });
  }
 
  isOtherDetails = false;
  addDetails(){
    this.isOtherDetails = true;
    this.othersDetailsData = [];

    this.getequipmentBydept(this.in_department);
    this.getdepartmentalDocu(this.in_department);

  }
  viewDetails(value){
    this.othersDetailsData1 =[];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
  }
  isOtherDetails1 = false;
  othersDetailsData1 =[];
  othersDetailsData =[];


  delOtherDetails(index){
    this.othersDetailsData.splice(index,1);
  }


  timing = 0;
  type;

  AddOtherDetails(data){
    if(!data.valid){
      alertify.console.error("All Field Required!!!!!!!!!");
      return;
    }

    let temp =  data.value;
    temp['attendance'] = 'Pending';
    temp['fileID'] =  this.selectedDoc['id'];
    temp['fileNm'] =  this.selectedDoc['upload'];
    temp['training_start_time'] = 'ON';
    temp['training_end_time'] = 'Pending';
    temp['exam'] = 'Pending';
    temp['timing'] = this.timing;
    this.othersDetailsData.push(temp);

    data.reset();
    this.timing = 0;
  }

  AddOtherDetails1(){
    
 const optionsArray = [
  { sub_type: 'Gowing / degowing', exam:'Pending', subject: 'General', type: 'Practical' ,attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending'},
  { sub_type: 'Diff. Section In The Dept.', exam:'Pending', subject: 'General', type: 'Practical',attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending' },
  { sub_type: 'Man And Material', exam:'Pending', subject: 'General', type: 'Practical',attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending' },
  { sub_type: 'Process Flow And Briefing', exam:'Pending', subject: 'General', type: 'Practical' ,attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending'},
  { sub_type: 'Selty In The Dept.', exam:'Pending', subject: 'General', type: 'Practical' ,attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending'},
  { sub_type: 'Briefing On The Departmental Procedures', exam:'Pending', subject: 'General', type: 'Practical',attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending' },
  { sub_type: 'In-Process checks', exam:'Pending', subject: 'General', type: 'Practical',attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending' },
  { sub_type: 'Instruments Used For In Process Checks', exam:'Pending', subject: 'General', type: 'Practical',attendance: 'Pending',fileNm:'',fileID:'',training_start_time:'ON',timing:0, training_end_time:'Pending' }
];



this.othersDetailsData.push(...optionsArray);

  
 
  }





 

  getTrainingNeeds() {
    this.service.get('training.php?type=getOJTTraining&deptmt101='+localStorage.getItem('department')).subscribe((response) => {
      this.trainings = response;
    });
  }
  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  employees1;
  onCheckboxChange(event: Event) {
    const checkbox = event.target as HTMLInputElement;
    if (checkbox.checked) {
      console.log('Checkbox is checked');
      let len = this.employees1.length;
      for (let i = 0; i < len; i++) {
        let len = Object.keys(this.employeeList).length;
        this.employeeList[len] = this.employees1[i];
      }
    } else {
      console.log('Checkbox is unchecked');
      this.employeeList = [];
    }
  }

  getEmployees() {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + localStorage.getItem('department')).subscribe((response) => {
        this.employees = response;
        this.employees1 = response;
      });
  }

 

  selectEmp(index) {
   let index1 = index - 1;
    if (index !== -1) {
      this.selectedEmp = this.employees[index1];
    }
  }

  getTrainers() {
    this.service
      .get('training.php?type=getExternalTrainers')
      .subscribe((response) => {
        this.trainers = response;
      });
  }


selectedDoc = [];

  getDocDetails(index){
      this.selectedDoc = this.documents[index - 1];
  }




  in_department;

  addEmployees(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }
 
    let temp = data.value;

    temp['firstname']  = this.selectedEmp['firstname'];
    temp['lastname']  = this.selectedEmp['lastname'];
    temp['othersDetailsData']  = this.othersDetailsData;
   
     
      this.employeeList.push(temp) ;
      this.selectedEmp = [];
      this.othersDetailsData = [];

      data.reset();
      this.isOtherDetails = false;
  }


  saveTrainingNeeds(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    data = data.value;
    data['employees'] = this.employeeList;
    this.service.post('training.php?type=saveOJTTrainingNeeds', JSON.stringify(data)).subscribe((response) => {
        if (response['status'] == 'success') {
          alert('Training has been allocated to employee');
          this.getTrainingNeeds();
          this.isNewTraining = false;
        } else {
          alert('An error occured');
        }
      });
  }

  add_subject;
  reference_document = '';
  checkOther(value, index) {
    if (value == 'Other') {
      this.add_subject = '';
      this.add_subject = true;
    } else {
      index = index - 1;

      this.reference_document = this.subject_list[index].training_desc;
    }
  }

  subject_list;
  getsubject() {
    this.service
      .get(
        'training.php?type=getsubject1&training_category=' +
          this.training_category
      )
      .subscribe((response) => {
        this.subject_list = response;
      });
  }

  documents;

  getequipmentBydept(dept_name) {
    this.service.get('training.php?type=getequipmentBydept&dept_name='+dept_name).subscribe(response => {
      this.equipments = response;
    });
  }
  getdepartmentalDocu(dept_name) {
    this.service.get('qa/document.php?type=getDocumentsByDept&deptName='+dept_name).subscribe(response => {
      this.documents = response;
    });
  }

  equipments;





  subject_name;
  add_subject_name(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
    this.service
      .post('training.php?type=addsubjects', JSON.stringify(temp))
      .subscribe((response) => {
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

  viewNeeds(index) {
    this.selectedNeed = this.trainings[index];
    this.isView = true;
  }
  downloadreport() {
    this.service.open('pdf1/training.php?type=identificationneedslog');
  }
  downloadview(value) {
    this.service.open(
      'pdf1/training.php?type=identificationoftrainingneeds&id=' + value
    );
  }

  // -----------------------------------------12th july------------------------------------------//

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












  selectedGrades: string[] = [];
  selectedGradesString: string = '';

  updateSelectedGrades() {
    this.selectedGradesString = this.selectedGrades.join(', ');
  }

 
 

  saveOJT(data) {

    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
    temp['departments'] = this.selectedGradesString;
    this.service.post('training.php?type=saveOJT',JSON.stringify(temp))
    .subscribe(response => {
      if (response['status'] == 'success') {
        data.reset();
        this.selectedGradesString = '';
         alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }


 


}
