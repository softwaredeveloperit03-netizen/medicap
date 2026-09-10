import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-schedule',
  templateUrl: './schedule.component.html',
  styleUrls: ['./schedule.component.css']
})
export class ScheduleComponent implements OnInit {

 
  ispending = false;
  isView = false;
  results;
  isNewTraining = false;
   trainings;
  employees;
  selectedTraining = [];
  employeeList = [];
  selectedEmp = [];
  trainers;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getScheduleLog();
    this.getTrainings();
     this.getEmployees();
    this.getDepartments();
    this.getTrainers();
  }


  feedbackRequired = 'NO';

  departments;

  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe((response) => {
      this.departments = response;
    });
  }

  viewDetails(value){
    this.othersDetailsData1 =[];
    this.isOtherDetails1 = true;
    this.othersDetailsData1 = value;
  }
  isOtherDetails1 = false;
  othersDetailsData1 =[];


  getScheduleLog() {
    this.service.get('training.php?type=getOJTScheduleLogForDept&training_category=Level 3 ( On The Job Training )&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.results = response;
    });
  }

  getTrainings() {
    this.service.get('training.php?type=getPendingOJTTrainings&training_category=Level 3 ( On The Job Training )&dept_name=' + localStorage.getItem('department')).subscribe(response => {
      this.trainings = response;
    });
  }

  getTrainers() {
    this.service.get('training.php?type=getExternalTrainers').subscribe(response => {
      this.trainers = response;
    });
  }
  
 

  getEmployees() {
    this.service
      .get(
        'hrDepartment.php?type=getEmployeesByDepartment101&deptmt101=' + localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.employees = response;
       });
  }

 
  selectEmp(index) {
    index = index - 1;
    this.selectedEmp= this.employees[index];
  }

  addEmployees() {
    let len = Object.keys(this.employeeList).length;
    this.employeeList[len] = this.selectedEmp;
    this.selectedEmp = [];
  }

  viewTraining(index) {
    this.selectedTraining = this.trainings[index];
    this.isNewTraining = true;
    this.ispending = false;
    this.assignColors();
  }



  materialCodeColors: { [key: string]: string } = {};
  colorList: string[] = [
    '#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFECB3', '#D1C4E9', '#B2DFDB', '#FFF9C4', '#FFCCBC',
    '#F8BBD0', '#DCEDC8', '#B3E5FC', '#FFE0B2', '#E1BEE7', '#B2EBF2', '#FFF9C4', '#FFCCBC',
    '#EF9A9A', '#A5D6A7', '#90CAF9', '#FFE082', '#CE93D8', '#80DEEA', '#FFEB3B', '#FFAB91',
    '#E57373', '#81C784', '#64B5F6', '#FFD54F', '#BA68C8', '#4DD0E1', '#FFEB3B', '#FF7043',
    '#EF5350', '#66BB6A', '#42A5F5', '#FFCA28', '#AB47BC', '#26C6DA', '#FFEB3B', '#FF5722',
    '#F44336', '#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#00BCD4', '#FFEB3B', '#E64A19',
    '#E53935', '#43A047', '#1E88E5', '#FFB300', '#8E24AA', '#00ACC1', '#FDD835', '#D84315',
    '#D32F2F', '#388E3C', '#1976D2', '#FFA000', '#7B1FA2', '#0097A7', '#FBC02D', '#BF360C',
    '#C62828', '#2E7D32', '#1565C0', '#FF8F00', '#6A1B9A', '#00838F', '#F9A825', '#FF6F00',
    '#B71C1C', '#1B5E20', '#0D47A1', '#FF6F00', '#4A148C', '#006064', '#F57F17', '#E65100',
    '#D50000', '#00C853', '#2962FF', '#FFD600', '#AA00FF', '#00B8D4', '#C6FF00', '#DD2C00',
    '#FF1744', '#00E676', '#2979FF', '#FFC400', '#D500F9', '#00BFA5', '#AEEA00', '#FF3D00',
    '#F50057', '#69F0AE', '#448AFF', '#FFAB00', '#651FFF', '#00E5FF', '#76FF03', '#FF9100',
    '#FF4081', '#B2FF59', '#40C4FF', '#FFD740', '#7C4DFF', '#18FFFF', '#CCFF90', '#FFAB40'
  ];

  assignColors(): void {
    let colorIndex = 0;
    this.selectedTraining['employees'].forEach(result => {
      if (!this.materialCodeColors[result.emp_id]) {
        this.materialCodeColors[result.emp_id] = this.colorList[colorIndex % this.colorList.length];
        colorIndex++;
      }
    });
  }


  checkAndPaste(date: string, dept: string, check: string): void {

    this.selectedTraining['employees'].forEach(emp => {
      // Ensure that the date is set only for employees within the same department

      console.log('emp.in_department'+emp.in_department);
      console.log('dept'+dept);


      if (emp.in_department === dept) {
        if (check === 'FROM') {
          emp.empTraningDateFrom = date;
        } else if (check === 'TO') {
          emp.empTraningDateTo = date;
        }
      }
    });
 
  }



  view(index) {
    this.selectedTraining = this.results[index];
    this.isView = true;
  }

  saveScheduleTraining(data) {
    let test = data.value;
    test['id'] = this.selectedTraining['id'];
    test['empList'] = this.selectedTraining['employees'];
    test['proposed_trainer'] = 'NA';
    test['Venue'] = 'NA';
    test['feedbackRequired'] = this.feedbackRequired;
    this.service.post('training.php?type=saveOJTScheduleTraining', JSON.stringify(test)).subscribe(response => {
      if (response['status'] == 'success') {
        this.selectedTraining = [];
        this.employeeList = [];
        this.selectedEmp = [];
        this.isNewTraining = false;
        this.getScheduleLog();
        this.getTrainings();
        this.ispending = true;
        alert('successfully saved');
      } else {
        alert('An error occured');
      }
    });
  }



  download(id) {
    this.service.open('purchase/training.php?type=onJobScheduleLog&id='+id);  
   }


}
